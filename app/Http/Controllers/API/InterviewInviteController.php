<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\AuthHelper;
use App\Helpers\FreeActionsHelper;
use App\Helpers\NotificationHelper;
use App\Jobs\SendUserPushJob;
use App\Services\Notifications\EmailNotificationService;
use App\Services\ActivityTracker;
use App\Enums\ActivityType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InterviewInviteController extends Controller
{
    // Invites that block a new invite between the same firm + student.
    private const ACTIVE_INVITE_STATUSES   = ['pending', 'accepted'];
    private const ACTIVE_INTERVIEW_STATUSES = ['scheduled', 'confirmed'];

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */
    private function resolveFirm(Request $request)
    {
        $user = AuthHelper::resolveUser($request);
        if (!$user) {
            return [null, response()->json(['status' => false, 'message' => 'Invalid token'], 401)];
        }
        if ($user->role !== 'firm') {
            return [null, response()->json(['status' => false, 'message' => 'Only firms can perform this action'], 403)];
        }
        $firm = DB::table('firm_profiles')->where('user_id', $user->id)->first();
        if (!$firm) {
            return [null, response()->json(['status' => false, 'message' => 'Firm profile not found'], 404)];
        }
        return [$firm, null];
    }

    private function resolveStudent(Request $request)
    {
        $user = AuthHelper::resolveUser($request);
        if (!$user) {
            return [null, response()->json(['status' => false, 'message' => 'Invalid token'], 401)];
        }
        if ($user->role !== 'student') {
            return [null, response()->json(['status' => false, 'message' => 'Only students can perform this action'], 403)];
        }
        return [$user, null];
    }

    /*
    |--------------------------------------------------------------------------
    | Firm: Invite candidate to interview  (POST /candidates/{studentId}/invite-interview)
    |--------------------------------------------------------------------------
    */
    public function invite(Request $request, $studentId = null)
    {
        try {
            [$firm, $err] = $this->resolveFirm($request);
            if ($err) return $err;

            $student = DB::table('users')
                ->where('id', $studentId)
                ->where('role', 'student')
                ->where('is_deleted', false)
                ->first();
            if (!$student) {
                return response()->json(['status' => false, 'message' => 'Candidate not found'], 404);
            }

            // Duplicate prevention: block if an active invite already exists.
            $active = DB::table('interview_invites')
                ->where('firm_id', $firm->id)
                ->where('student_id', $studentId)
                ->where(function ($q) {
                    $q->whereIn('invite_status', self::ACTIVE_INVITE_STATUSES)
                        ->orWhereIn('interview_status', self::ACTIVE_INTERVIEW_STATUSES);
                })
                ->first();
            if ($active) {
                return response()->json([
                    'status'  => false,
                    'reason'  => 'invite_exists',
                    'message' => 'An active interview invitation already exists for this candidate.',
                ], 409);
            }

            // Free action limit: sending an invite consumes a free action for
            // non-premium firms (counted via the interview_invite recruiter_action below).
            $freeCheck = FreeActionsHelper::canPerformFreeAction($firm->id);
            if (!$freeCheck['allowed']) {
                return response()->json([
                    'status'  => false,
                    'reason'  => 'free_limit_reached',
                    'message' => $freeCheck['message'],
                ], 403);
            }

            $message = $request->input('message')
                ?: ($firm->firm_name . ' has invited you for an interview.');

            // active_flag = 1 marks this as THE active invite for the pair. A unique
            // index on (firm_id, student_id, active_flag) makes duplicate active
            // invites impossible even under a concurrent race (the pre-check above
            // is the friendly path; this is the race-safe backstop).
            try {
                $inviteId = DB::table('interview_invites')->insertGetId([
                    'firm_id'          => $firm->id,
                    'student_id'       => $studentId,
                    'message'          => $message,
                    'invite_status'    => 'pending',
                    'interview_status' => 'not_scheduled',
                    'active_flag'      => 1,
                    'invited_at'       => now(),
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);
            } catch (\Illuminate\Database\QueryException $e) {
                if ((int) ($e->errorInfo[1] ?? 0) === 1062) { // duplicate active invite (race lost)
                    return response()->json([
                        'status'  => false,
                        'reason'  => 'invite_exists',
                        'message' => 'An active interview invitation already exists for this candidate.',
                    ], 409);
                }
                throw $e;
            }

            // In-app notification (reuses recruiter_actions feed).
            DB::table('recruiter_actions')->insert([
                'firm_id'             => $firm->id,
                'student_id'          => $studentId,
                'visible_to'          => 'student',
                'interview_invite_id' => $inviteId,
                'action_type'         => 'interview_invite',
                'title'               => 'Interview invitation',
                'message'             => $firm->firm_name . ' has invited you for an interview.',
                'action_status'       => 'pending',
                'created_at'          => now(),
            ]);

            // Push notification (additive layer — queued, never blocks the request).
            SendUserPushJob::dispatch(
                (int) $studentId,
                $firm->firm_name . ' invited you for an interview',
                'Tap to view and respond.',
                '/recruiter-actions',
                [],
                'interview_' . $inviteId // later invite events replace this notification
            );

            // Email (immediate / queued).
            try {
                app(EmailNotificationService::class)->sendInterviewInvite(
                    $student->email,
                    $student->name,
                    $firm->firm_name,
                    $message
                );
            } catch (\Throwable $e) {
                Log::error('Failed to queue interview invite email', [
                    'invite_id' => $inviteId,
                    'error'     => $e->getMessage(),
                ]);
            }

            // Activity log (async, non-blocking).
            ActivityTracker::log(ActivityTracker::FIRM, $firm->user_id, ActivityType::INTERVIEW_INVITE_SENT, [
                'invite_id'  => $inviteId,
                'student_id' => (int) $studentId,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Interview invitation sent',
                'data'    => [
                    'invite_id'        => (string) $inviteId,
                    'invite_status'    => 'pending',
                    'interview_status' => 'not_scheduled',
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Invite To Interview Error', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['status' => false, 'message' => 'Unexpected server error while sending invite.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Firm: Current invite state for a candidate  (GET /candidates/{studentId}/interview-invite)
    |--------------------------------------------------------------------------
    */
    public function candidateInvite(Request $request, $studentId = null)
    {
        [$firm, $err] = $this->resolveFirm($request);
        if ($err) return $err;

        $invite = DB::table('interview_invites')
            ->where('firm_id', $firm->id)
            ->where('student_id', $studentId)
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'status' => true,
            'data'   => $invite ? $this->formatInvite($invite) : null,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Student: Accept / Decline invite  (POST /interview-invites/{id}/respond)
    |--------------------------------------------------------------------------
    */
    public function respond(Request $request, $inviteId = null)
    {
        try {
            $request->validate(['response' => 'required|string|in:Accepted,Declined']);

            [$user, $err] = $this->resolveStudent($request);
            if ($err) return $err;

            $invite = DB::table('interview_invites')
                ->where('id', $inviteId)
                ->where('student_id', $user->id)
                ->first();
            if (!$invite) {
                return response()->json(['status' => false, 'message' => 'Invitation not found'], 404);
            }
            if ($invite->invite_status !== 'pending') {
                return response()->json(['status' => false, 'message' => 'Invitation already responded'], 409);
            }

            $newStatus = $request->response === 'Accepted' ? 'accepted' : 'declined';

            $inviteUpdate = [
                'invite_status' => $newStatus,
                'responded_at'  => now(),
                'updated_at'    => now(),
            ];
            // Decline frees the pair for a future invite (active_flag -> NULL).
            if ($newStatus === 'declined') {
                $inviteUpdate['active_flag'] = null;
            }
            DB::table('interview_invites')->where('id', $inviteId)->update($inviteUpdate);

            DB::table('recruiter_actions')
                ->where('interview_invite_id', $inviteId)
                ->where('action_type', 'interview_invite')
                ->update(['action_status' => $newStatus]);

            // Notify the firm (lands in the firm's notification bell + email).
            $firm = DB::table('firm_profiles')
                ->join('users', 'firm_profiles.user_id', '=', 'users.id')
                ->where('firm_profiles.id', $invite->firm_id)
                ->select('firm_profiles.user_id', 'firm_profiles.firm_name', 'users.email as firm_email')
                ->first();
            if ($firm) {
                $verb = $newStatus === 'accepted' ? 'accepted' : 'declined';
                NotificationHelper::create(
                    $firm->user_id,
                    'Interview invitation ' . $verb,
                    $user->name . ' has ' . $verb . ' your interview invitation.',
                    false // explicit richer push dispatched below
                );

                // Push notification (additive layer — queued, never blocks the request).
                SendUserPushJob::dispatch(
                    (int) $firm->user_id,
                    $user->name . ' ' . $verb . ' your interview invite',
                    $newStatus === 'accepted'
                        ? 'You can now schedule the interview.'
                        : 'The invitation has been closed.',
                    '/firm-dashboard',
                    [],
                    'interview_' . $invite->id // replaces older notifications for this invite
                );
                try {
                    app(EmailNotificationService::class)->sendInterviewInviteResponse(
                        $firm->firm_email,
                        $firm->firm_name,
                        $user->name,
                        $newStatus === 'accepted'
                    );
                } catch (\Throwable $e) {
                    Log::error('Failed to queue interview invite response email', [
                        'invite_id' => $inviteId,
                        'error'     => $e->getMessage(),
                    ]);
                }
            }

            // Activity log (async, non-blocking) — only the accept is a tracked action.
            if ($newStatus === 'accepted') {
                ActivityTracker::log(ActivityTracker::STUDENT, $user->id, ActivityType::INTERVIEW_ACCEPTED, [
                    'invite_id' => (int) $inviteId,
                    'firm_id'   => (int) $invite->firm_id,
                ]);
            }

            return response()->json([
                'status'  => true,
                'message' => $newStatus === 'accepted' ? 'Invitation accepted' : 'Invitation declined',
                'data'    => ['invite_status' => $newStatus],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Respond Interview Invite Error', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['status' => false, 'message' => 'Unexpected server error.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Firm: Schedule the interview for an accepted invite  (POST /interview-invites/{id}/schedule)
    |--------------------------------------------------------------------------
    */
    public function schedule(Request $request, $inviteId = null)
    {
        try {
            $request->validate([
                'interview_date' => 'required|date',
                'interview_mode' => 'required|string|in:Physical,Telephonic,To Be Discussed',
                'interview_note' => 'nullable|string',
            ]);

            [$firm, $err] = $this->resolveFirm($request);
            if ($err) return $err;

            $invite = DB::table('interview_invites')
                ->where('id', $inviteId)
                ->where('firm_id', $firm->id)
                ->first();
            if (!$invite) {
                return response()->json(['status' => false, 'message' => 'Invitation not found'], 404);
            }
            if ($invite->invite_status !== 'accepted') {
                return response()->json(['status' => false, 'message' => 'Candidate has not accepted the invitation yet'], 409);
            }

            // NOTE: no free-action gate here. The free action was already consumed
            // when the invite was SENT (invite()). Scheduling an already-accepted
            // invite is a continuation of that paid action, not a new charge — and
            // gating it would block the firm from scheduling the very interview it
            // already paid to invite for.

            DB::table('interview_invites')->where('id', $inviteId)->update([
                'interview_status'           => 'scheduled',
                'interview_date'             => $request->interview_date,
                'interview_mode'             => $request->interview_mode,
                'interview_note'             => $request->interview_note,
                'student_interview_response' => 'Pending',
                'scheduled_at'               => now(),
                'updated_at'                 => now(),
            ]);

            DB::table('recruiter_actions')->insert([
                'firm_id'             => $firm->id,
                'student_id'          => $invite->student_id,
                'visible_to'          => 'student',
                'interview_invite_id' => $inviteId,
                'action_type'         => 'interview_invite',
                'title'               => 'Interview scheduled',
                'message'             => $firm->firm_name . ' scheduled your interview. Please confirm your availability.',
                'action_status'       => 'scheduled',
                'created_at'          => now(),
            ]);

            // Push notification (additive layer — queued, never blocks the request).
            $pushWhen = date('D, d M Y \a\t h:i A', strtotime($request->interview_date));
            SendUserPushJob::dispatch(
                (int) $invite->student_id,
                $firm->firm_name . ' scheduled your interview',
                $pushWhen . ' · ' . $request->interview_mode . ' — please confirm your availability.',
                '/recruiter-actions',
                [],
                'interview_' . $invite->id // replaces the original invite notification
            );

            // Activity log (async, non-blocking).
            ActivityTracker::log(ActivityTracker::FIRM, $firm->user_id, ActivityType::INTERVIEW_SCHEDULED, [
                'invite_id'      => (int) $inviteId,
                'student_id'     => (int) $invite->student_id,
                'interview_date' => $request->interview_date,
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Interview scheduled',
                'data'    => ['interview_status' => 'scheduled'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Schedule Interview Invite Error', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['status' => false, 'message' => 'Unexpected server error.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Student: Confirm / Reschedule a scheduled invite  (POST /interview-invites/{id}/confirm)
    |--------------------------------------------------------------------------
    */
    public function confirm(Request $request, $inviteId = null)
    {
        try {
            $request->validate([
                'response'        => 'required|string|in:Confirmed,Reschedule Requested',
                'reschedule_date' => 'nullable|date',
                'reschedule_note' => 'nullable|string',
            ]);

            [$user, $err] = $this->resolveStudent($request);
            if ($err) return $err;

            $invite = DB::table('interview_invites')
                ->where('id', $inviteId)
                ->where('student_id', $user->id)
                ->first();
            if (!$invite) {
                return response()->json(['status' => false, 'message' => 'Invitation not found'], 404);
            }
            if ($invite->interview_status !== 'scheduled') {
                return response()->json(['status' => false, 'message' => 'No scheduled interview to respond to'], 409);
            }

            $update = [
                'student_interview_response' => $request->response,
                'student_response_note'      => $request->reschedule_note,
                'updated_at'                 => now(),
            ];
            if ($request->response === 'Confirmed') {
                $update['interview_status'] = 'confirmed';
            } else {
                $update['reschedule_date'] = $request->reschedule_date;
            }

            DB::table('interview_invites')->where('id', $inviteId)->update($update);

            DB::table('recruiter_actions')
                ->where('interview_invite_id', $inviteId)
                ->where('action_type', 'interview_invite')
                ->where('action_status', 'scheduled')
                ->update([
                    'action_status' => $request->response === 'Confirmed' ? 'confirmed' : 'reschedule_requested',
                ]);

            // Notify the firm (lands in the firm's notification bell).
            $firm = DB::table('firm_profiles')->where('id', $invite->firm_id)->first();
            if ($firm) {
                $confirmed = $request->response === 'Confirmed';
                NotificationHelper::create(
                    $firm->user_id,
                    $confirmed ? 'Interview confirmed' : 'Reschedule requested',
                    $confirmed
                        ? $user->name . ' confirmed the scheduled interview.'
                        : $user->name . ' requested to reschedule the interview.',
                    false // explicit richer push dispatched below
                );

                // Push notification (additive layer — queued, never blocks the request).
                SendUserPushJob::dispatch(
                    (int) $firm->user_id,
                    $confirmed
                        ? $user->name . ' confirmed the interview'
                        : $user->name . ' requested a new interview time',
                    $confirmed
                        ? 'The interview is locked in.'
                        : ($request->reschedule_date
                            ? 'Proposed: ' . date('D, d M Y', strtotime($request->reschedule_date))
                            : 'Review the reschedule request.'),
                    '/firm-dashboard',
                    [],
                    'interview_' . $invite->id // replaces older notifications for this invite
                );
            }

            return response()->json([
                'status'  => true,
                'message' => $request->response === 'Confirmed' ? 'Interview confirmed' : 'Reschedule requested',
                'data'    => ['student_interview_response' => $request->response],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['status' => false, 'message' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('Confirm Interview Invite Error', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['status' => false, 'message' => 'Unexpected server error.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Firm: Mark interview completed  (POST /interview-invites/{id}/complete)
    |--------------------------------------------------------------------------
    */
    public function complete(Request $request, $inviteId = null)
    {
        try {
            [$firm, $err] = $this->resolveFirm($request);
            if ($err) return $err;

            $invite = DB::table('interview_invites')
                ->where('id', $inviteId)
                ->where('firm_id', $firm->id)
                ->first();
            if (!$invite) {
                return response()->json(['status' => false, 'message' => 'Invitation not found'], 404);
            }
            if (!in_array($invite->interview_status, ['scheduled', 'confirmed'], true)) {
                return response()->json(['status' => false, 'message' => 'Interview is not in a completable state'], 409);
            }

            DB::table('interview_invites')->where('id', $inviteId)->update([
                'interview_status' => 'completed',
                'active_flag'      => null, // frees the pair for a future invite
                'updated_at'       => now(),
            ]);
            DB::table('recruiter_actions')
                ->where('interview_invite_id', $inviteId)
                ->where('action_type', 'interview_invite')
                ->update(['action_status' => 'completed']);

            return response()->json([
                'status'  => true,
                'message' => 'Interview marked as completed',
                'data'    => ['interview_status' => 'completed'],
            ]);
        } catch (\Exception $e) {
            Log::error('Complete Interview Invite Error', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['status' => false, 'message' => 'Unexpected server error.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Firm OR Candidate: Cancel an active invite  (POST /interview-invites/{id}/cancel)
    |--------------------------------------------------------------------------
    */
    public function cancel(Request $request, $inviteId = null)
    {
        try {
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
            }

            $invite = DB::table('interview_invites')->where('id', $inviteId)->first();
            if (!$invite) {
                return response()->json(['status' => false, 'message' => 'Invitation not found'], 404);
            }

            // Authorize: the firm that owns it, or the invited student.
            $isOwnerFirm = false;
            if ($user->role === 'firm') {
                $firm = DB::table('firm_profiles')->where('user_id', $user->id)->first();
                $isOwnerFirm = $firm && (int) $firm->id === (int) $invite->firm_id;
            }
            $isOwnerStudent = $user->role === 'student' && (int) $user->id === (int) $invite->student_id;
            if (!$isOwnerFirm && !$isOwnerStudent) {
                return response()->json(['status' => false, 'message' => 'Not authorized to cancel this invitation'], 403);
            }

            $active = in_array($invite->invite_status, self::ACTIVE_INVITE_STATUSES, true)
                || in_array($invite->interview_status, self::ACTIVE_INTERVIEW_STATUSES, true);
            if (!$active) {
                return response()->json(['status' => false, 'message' => 'Invitation is not active'], 409);
            }

            DB::table('interview_invites')->where('id', $inviteId)->update([
                'interview_status' => 'cancelled',
                'active_flag'      => null, // frees the pair for a future invite
                'updated_at'       => now(),
            ]);
            DB::table('recruiter_actions')
                ->where('interview_invite_id', $inviteId)
                ->where('action_type', 'interview_invite')
                ->update(['action_status' => 'cancelled']);

            // Notify the firm bell if the candidate cancelled.
            if ($isOwnerStudent) {
                $firm = DB::table('firm_profiles')->where('id', $invite->firm_id)->first();
                if ($firm) {
                    NotificationHelper::create(
                        $firm->user_id,
                        'Interview cancelled',
                        'The candidate cancelled the interview invitation.',
                        false // explicit richer push dispatched below
                    );

                    // Push notification (additive layer — queued, never blocks the request).
                    SendUserPushJob::dispatch(
                        (int) $firm->user_id,
                        $user->name . ' cancelled the interview',
                        'The interview slot is now free.',
                        '/firm-dashboard',
                        [],
                        'interview_' . $invite->id // replaces older notifications for this invite
                    );
                }
            }

            return response()->json([
                'status'  => true,
                'message' => 'Interview invitation cancelled',
                'data'    => ['interview_status' => 'cancelled', 'cancelled_by' => $isOwnerFirm ? 'firm' : 'candidate'],
            ]);
        } catch (\Exception $e) {
            Log::error('Cancel Interview Invite Error', ['message' => $e->getMessage(), 'line' => $e->getLine()]);
            return response()->json(['status' => false, 'message' => 'Unexpected server error.'], 500);
        }
    }

    private function formatInvite(object $invite): array
    {
        return [
            'id'                         => (string) $invite->id,
            'invite_status'              => $invite->invite_status,
            'interview_status'           => $invite->interview_status,
            'interview_date'             => $invite->interview_date,
            'interview_mode'             => $invite->interview_mode,
            'interview_note'             => $invite->interview_note,
            'student_interview_response' => $invite->student_interview_response,
        ];
    }
}
