<?php

namespace App\Jobs;

use App\Helpers\NotificationHelper;
use App\Services\SystemSettingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduled job — runs hourly (Phase 2 interview credit lifecycle).
 *
 * Auto-expires scheduled interviews the student never responded to, in BOTH
 * interview flows. Because a credit is only consumed at confirmation, an
 * expired interview consumes NOTHING — the credit is simply never charged, so
 * there is no refund to make here. This job only transitions stale rows to a
 * terminal 'expired' state (stopping reminders and freeing the invite pair).
 *
 * The window is admin-configurable via
 * SystemSettingService::getInterviewConfirmationTimeoutDays() (default 5 days).
 * Only rows where the STUDENT is the blocker are expired — a pending reschedule
 * request (ball in the firm's court) is left alone.
 *
 * Per-row try/catch isolates failures; no business flow can be affected.
 */
class ExpirePendingInterviewConfirmationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $days   = SystemSettingService::getInterviewConfirmationTimeoutDays();
        $cutoff = now()->subDays($days);

        $this->expireInviteFlow($cutoff);
        $this->expireApplicationsFlow($cutoff);
    }

    /**
     * Invite flow (interview_invites): scheduled, student response still
     * pending, scheduled before the cutoff. scheduled_at is rewritten on every
     * (re)schedule, so the window always tracks the latest schedule.
     */
    private function expireInviteFlow(\Illuminate\Support\Carbon $cutoff): void
    {
        $invites = DB::table('interview_invites as ii')
            ->leftJoin('firm_profiles as fp', 'fp.id', '=', 'ii.firm_id')
            ->where('ii.interview_status', 'scheduled')
            ->whereNull('ii.interview_credit_consumed_at')
            ->where(function ($q) {
                $q->whereNull('ii.student_interview_response')
                    ->orWhere('ii.student_interview_response', 'Pending');
            })
            ->whereNotNull('ii.scheduled_at')
            ->where('ii.scheduled_at', '<', $cutoff)
            ->select('ii.id', 'ii.student_id', 'fp.user_id as firm_user_id', 'fp.firm_name')
            ->get();

        foreach ($invites as $invite) {
            try {
                DB::transaction(function () use ($invite) {
                    DB::table('interview_invites')->where('id', $invite->id)->update([
                        'interview_status' => 'expired',
                        'active_flag'      => null, // frees the pair for a future invite
                        'updated_at'       => now(),
                    ]);
                    DB::table('recruiter_actions')
                        ->where('interview_invite_id', $invite->id)
                        ->where('action_type', 'interview_invite')
                        ->update(['action_status' => 'expired']);
                });

                // Best-effort bell notifications (no credit was consumed).
                if (!empty($invite->firm_user_id)) {
                    NotificationHelper::create(
                        (int) $invite->firm_user_id,
                        'Interview expired',
                        'The candidate did not confirm the interview in time, so it has expired. No interview credit was used.',
                        false,
                        '/firm-applications'
                    );
                }
                NotificationHelper::create(
                    (int) $invite->student_id,
                    'Interview invitation expired',
                    ($invite->firm_name ?: 'A firm') . "'s interview invitation expired because it was not confirmed in time.",
                    false,
                    '/recruiter-actions'
                );
            } catch (Throwable $e) {
                Log::error('Expire interview (invite flow) failed', ['invite_id' => $invite->id, 'error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Applications flow (applications): interview requested/scheduled, student
     * response still pending, requested (or reschedule-accepted) before cutoff.
     * Clock = the later of reschedule_accepted_at and interview_requested_at.
     */
    private function expireApplicationsFlow(\Illuminate\Support\Carbon $cutoff): void
    {
        $apps = DB::table('applications as a')
            ->join('jobs as j', 'j.id', '=', 'a.job_id')
            ->leftJoin('firm_profiles as fp', 'fp.id', '=', 'j.firm_id')
            ->whereIn('a.recruiter_status', ['Interview Requested', 'Interview Scheduled'])
            ->where('a.student_interview_response', 'Pending')
            ->whereNull('a.interview_credit_consumed_at')
            ->whereRaw('COALESCE(a.reschedule_accepted_at, a.interview_requested_at) < ?', [$cutoff])
            ->select('a.id', 'a.student_id', 'fp.user_id as firm_user_id', 'fp.firm_name')
            ->get();

        foreach ($apps as $app) {
            try {
                DB::table('applications')->where('id', $app->id)->update([
                    'recruiter_status' => 'Interview Expired',
                    'updated_at'       => now(),
                ]);

                if (!empty($app->firm_user_id)) {
                    NotificationHelper::create(
                        (int) $app->firm_user_id,
                        'Interview expired',
                        'The candidate did not confirm the interview in time, so it has expired. No interview credit was used.',
                        false,
                        '/firm-applications'
                    );
                }
                NotificationHelper::create(
                    (int) $app->student_id,
                    'Interview expired',
                    ($app->firm_name ?: 'A firm') . "'s interview request expired because it was not confirmed in time.",
                    false,
                    '/recruiter-actions'
                );
            } catch (Throwable $e) {
                Log::error('Expire interview (applications flow) failed', ['application_id' => $app->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
