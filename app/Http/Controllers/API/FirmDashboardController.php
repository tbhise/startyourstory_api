<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\SubscriptionHelper;

class FirmDashboardController extends Controller
{

    public function getCandidates(Request $request)
    {
        try {
            $authUser = $request->attributes->get('auth_user');
            $firm = DB::table('firm_profiles')->where('user_id', $authUser->id)->first();
            $pageSize = $request->page_size ?? 12;
            $query = DB::table('users')
                ->select(
                    'users.id',
                    'users.name',
                    'student_profiles.looking_for',
                    'student_profiles.address',
                    'student_profiles.gender',
                    'student_profiles.passing_month',
                    'student_profiles.exposure_type',
                    'student_profiles.core_department',
                    'student_profiles.resume_path',
                    'student_profiles.registration_type',
                    'student_profiles.preferred_categories',
                    'student_profiles.availability_status',
                    'student_profiles.experience_years',
                    'users.profile_image',
                    // is_saved: derived from a 1:1 LEFT JOIN (saved_actions below)
                    // instead of a per-row correlated EXISTS subquery. Same value,
                    // same column position — byte-identical output, no dependent
                    // subquery in EXPLAIN.
                    DB::raw('IF(saved_actions.student_id IS NOT NULL, 1, 0) as is_saved'),
                    DB::raw('IF(users.email_verified_at IS NOT NULL, 1, 0) as is_verified')
                )
                ->leftJoin('student_profiles', 'users.id', '=', 'student_profiles.user_id')
                // Pre-aggregated saved-candidate ids for THIS firm. GROUP BY student_id
                // makes the derived table 1:1 with users, so this LEFT JOIN cannot
                // multiply rows or affect pagination/ordering. Powers is_saved above.
                ->leftJoin(
                    DB::raw('(SELECT student_id FROM recruiter_actions WHERE firm_id = ' . (int) $firm->id . " AND action_type = 'candidate_saved' GROUP BY student_id) AS saved_actions"),
                    'saved_actions.student_id',
                    '=',
                    'users.id'
                )
                ->where('users.is_deleted', false)
                // Hide students who have requested account deletion (30-day grace).
                // Reversible: a login clears deletion_requested_at and they reappear.
                ->whereNull('users.deletion_requested_at')
                ->where('users.role', 'student')
                // Only surface students who have completed their profile, so firms
                // never see sparse / half-filled candidate cards.
                ->where('users.profile_completed', true);

            // Exclude students who have marked themselves as JOINED a firm via the
            // Employment Status card (student_profiles.employment_status='joined') —
            // they are no longer job-seeking. NULL / 'looking' rows stay visible, so
            // existing candidates are unaffected. Reversible: switching back to
            // "looking" re-lists them. No new column — reuses the existing flag.
            $query->where(function ($q) {
                $q->whereNull('student_profiles.employment_status')
                    ->orWhere('student_profiles.employment_status', '!=', 'joined');
            });

            /*
        |--------------------------------------------------------------------------
        | Exclude already_doing_articleship from candidate search
        | These students are not job-seeking; they only appear in creator search
        | (handled below via the is_creator OR clause when creator tab is active).
        |--------------------------------------------------------------------------
        */
            $isCreatorTabActive = $request->filled('registered_for') &&
                in_array('creator',
                    is_array($request->registered_for)
                        ? $request->registered_for
                        : [$request->registered_for]
                );
            if (!$isCreatorTabActive) {
                $query->where('student_profiles.looking_for', '!=', 'already_doing_articleship');
            }

            /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('users.name', 'like', "%{$search}%")
                        ->orWhere('student_profiles.preferred_location', 'like', "%{$search}%")
                        ->orWhere('student_profiles.core_department', 'like', "%{$search}%");
                });
            }
            /*
        |--------------------------------------------------------------------------
        | City Filter — client-driven (empty = show all candidates)
        |--------------------------------------------------------------------------
        */
            $cities = $request->input('cities', []);
            if (!empty($cities)) {
                $query->where(function ($q) use ($cities) {
                    foreach ($cities as $city) {
                        $q->orWhereJsonContains('student_profiles.preferred_location', $city);
                    }
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Gender Filter
        |--------------------------------------------------------------------------
        */
            if ($request->filled('genders')) {
                $genders = is_array($request->genders)
                    ? $request->genders
                    : [$request->genders];
                $query->whereIn(
                    'student_profiles.gender',
                    $genders
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Department Filter
        |--------------------------------------------------------------------------
        */
            if ($request->filled('departments')) {
                $departments = is_array($request->departments)
                    ? $request->departments
                    : [$request->departments];
                $query->whereIn(
                    'student_profiles.core_department',
                    $departments
                );
            }

            /*
        |--------------------------------------------------------------------------
        | Creator Category Filter
        |--------------------------------------------------------------------------
        */
            if ($request->filled('categories')) {
                $categories = is_array($request->categories)
                    ? $request->categories
                    : [$request->categories];
                $query->where(function ($q) use ($categories) {
                    foreach ($categories as $cat) {
                        $q->orWhereJsonContains('student_profiles.preferred_categories', $cat);
                    }
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Registration type
        |--------------------------------------------------------------------------
        */
            if ($request->filled('registration_type')) {
                $query->where(
                    'student_profiles.registration_type',
                    strtolower($request->registration_type)
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Registered For
        |--------------------------------------------------------------------------
        */
            if ($request->filled('registered_for')) {
                $registeredFor = is_array($request->registered_for)
                    ? $request->registered_for
                    : [$request->registered_for];
                $query->where(function ($q) use ($registeredFor) {
                    $q->whereIn('student_profiles.looking_for', $registeredFor);
                    if (in_array('creator', $registeredFor)) {
                        $q->orWhere('student_profiles.is_creator', true);
                    }
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Passing Month
        |--------------------------------------------------------------------------
        */
            if ($request->filled('passing_months')) {
                $passingMonths = is_array($request->passing_months)
                    ? $request->passing_months
                    : [$request->passing_months];
                $query->whereIn(
                    'student_profiles.passing_month',
                    $passingMonths
                );
            }
            /*
        |--------------------------------------------------------------------------
        | Saved Only
        |--------------------------------------------------------------------------
        */
            if ($request->saved_only) {
                $query->whereExists(function ($q) use ($firm) {
                    $q->select(DB::raw(1))
                        ->from('recruiter_actions')
                        ->whereColumn(
                            'recruiter_actions.student_id',
                            'users.id'
                        )
                        ->where(
                            'recruiter_actions.firm_id',
                            $firm->id
                        )
                        ->where(
                            'recruiter_actions.action_type',
                            'candidate_saved'
                        );
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */
            switch ($request->sort) {
                case 'oldest':
                    $query->orderBy(
                        'student_profiles.created_at',
                        'asc'
                    );
                    break;
                case 'name':
                    $query->orderBy(
                        'users.name',
                        'asc'
                    );
                    break;
                default:
                    // Latest Login DESC — reuses the existing users.last_login_at
                    // (maintained by AuthController on every login; no new tracking).
                    // MySQL sorts NULLs last under DESC, so never-logged-in students
                    // fall to the end; profile recency breaks ties deterministically.
                    $query->orderByDesc('users.last_login_at')
                        ->orderByDesc('student_profiles.created_at');
                    break;
            }
            /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */
            $users = $query->paginate($pageSize);
            return response()->json([
                'status' => true,
                'data' => [
                    'students' =>
                    $users->items(),
                    'current_page' =>
                    $users->currentPage(),
                    'last_page' =>
                    $users->lastPage(),
                    'next_page_url' =>
                    $users->nextPageUrl(),
                    'total' =>
                    $users->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Get Candidates Error: ' .
                    $e->getMessage()
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error..!'
            ]);
        }
    }
    public function candidateDetail(Request $request)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | Auth User
        |--------------------------------------------------------------------------
        */
            $authUser =
                $request->attributes->get(
                    'auth_user'
                );
            /*
        |--------------------------------------------------------------------------
        | Firm Profile
        |--------------------------------------------------------------------------
        */
            $firm = DB::table('firm_profiles')
                ->where(
                    'user_id',
                    $authUser->id
                )
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Premium Status
        |--------------------------------------------------------------------------
        */
            $isPremium = false;
            if ($firm) {
                $isPremium =
                    SubscriptionHelper::isPremiumFirm(
                        $firm->id
                    );
            }
            /*
        |--------------------------------------------------------------------------
        | Candidate
        |--------------------------------------------------------------------------
        */
            $users = DB::table('users')
                ->leftJoin(
                    'student_profiles',
                    'users.id',
                    '=',
                    'student_profiles.user_id'
                )
                ->where(
                    'users.is_deleted',
                    false
                )
                ->where(
                    'users.role',
                    'student'
                )
                ->where(
                    'users.id',
                    $request->id
                )
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Not Found
        |--------------------------------------------------------------------------
        */
            if (!$users) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    'Candidate not found'
                ]);
            }
            /**
             * Is Shortlisted
             *
             */
            $is_shortlisted =  DB::table('recruiter_actions')->where('firm_id', $firm->id)->where('student_id', $request->id)->where('action_status', 'shortlisted')->exists();
            $is_reported =  DB::table('reported_profiles')->where('reported_by', $firm->user_id)->where('student_id', $request->id)->exists();

            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
            $data = (array) $users;
            // C2: never expose raw storage paths to the client. Surface only
            // existence + extension metadata; downloads go through /downloadFile
            // (student_id + type), which resolves the path server-side.
            $resumePath    = $data['resume_path'] ?? null;
            $marksheetPath = $data['marksheet_path'] ?? null;
            $ext = static fn($p) => $p ? strtolower(pathinfo((string) $p, PATHINFO_EXTENSION)) : null;
            unset(
                $data['password'],
                $data['api_token'],
                $data['token_expires_at'],
                $data['is_deleted'],
                $data['referred_by'],
                $data['user_id'],
                $data['resume_path'],
                $data['marksheet_path']
            );
            if (isset($data['preferred_categories']) && is_string($data['preferred_categories'])) {
                $data['preferred_categories'] = json_decode($data['preferred_categories']) ?? [];
            }

            return response()->json([
                'status' => true,
                'data' => [
                    ...$data,
                    'has_resume'    => !empty($resumePath),
                    'has_marksheet' => !empty($marksheetPath),
                    'resume_ext'    => $ext($resumePath),
                    'marksheet_ext' => $ext($marksheetPath),
                    'profile_image' => $users->profile_image
                        ? asset('storage/' . $users->profile_image)
                        : null,
                    'is_premium' => $isPremium,
                    'is_shortlisted' => $is_shortlisted ? 'Shortlisted' : null,
                    'is_reported' => $is_reported,
                    'is_verified' => !empty($users->email_verified_at),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Get candidateDetail Error: '
                    . $e->getMessage()
            );
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error..!'
            ]);
        }
    }
    public function downloadFile(Request $request)
    {
        // C2: the file location is NEVER taken from the client. The caller sends
        // student_id + type; the path is resolved from the DB. This closes the
        // previous arbitrary-file-read / path-traversal hole while preserving the
        // existing business rule (firms may download even without an application).
        $authUser  = $request->attributes->get('auth_user');
        $type      = $request->input('type');
        // Accept both snake_case (new) and camelCase (legacy frontend) keys.
        $studentId = $request->input('student_id', $request->input('studentId'));

        // Concise security audit log for every sensitive download attempt.
        $audit = function (string $result, string $reason = '') use ($authUser, $studentId, $type) {
            Log::info('[ResumeDownload] ' . $result, [
                'user_id'    => $authUser->id ?? null,
                'role'       => $authUser->role ?? null,
                'student_id' => $studentId,
                'type'       => $type,
                'reason'     => $reason,
                'at'         => now()->toDateTimeString(),
            ]);
        };

        try {
            // Allowed roles: Admin + Firm. This endpoint is firm-scoped (route is
            // behind FirmVerifiedMiddleware); resolving a firm profile also denies
            // students/guests as defense-in-depth.
            $firm = DB::table('firm_profiles')->where('user_id', $authUser->id ?? 0)->first();
            if (!$firm) {
                $audit('FAILURE', 'Blocked non-firm download attempt');
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            if (!in_array($type, ['resume', 'marksheet'], true)) {
                $audit('FAILURE', 'Invalid file type');
                return response()->json(['status' => false, 'message' => 'Invalid file type'], 422);
            }
            if (!$studentId || !ctype_digit((string) $studentId)) {
                $audit('FAILURE', 'Invalid student id');
                return response()->json(['status' => false, 'message' => 'Invalid student'], 422);
            }

            // Resolve the file path from the student record (never from input).
            $student = DB::table('student_profiles')->where('user_id', (int) $studentId)->first();
            if (!$student) {
                $audit('FAILURE', 'Student not found');
                return response()->json(['status' => false, 'message' => 'Student not found'], 404);
            }
            $path = $type === 'resume' ? $student->resume_path : $student->marksheet_path;
            if (!$path) {
                $audit('FAILURE', 'No ' . $type . ' uploaded');
                return response()->json(['status' => false, 'message' => 'No ' . $type . ' uploaded'], 404);
            }

            // Defense-in-depth: refuse any stored path that tries to escape the
            // public root (protects against tampered/legacy DB values too).
            if (str_contains($path, '..') || str_starts_with($path, '/') || str_contains($path, "\0")) {
                $audit('BLOCKED', 'Blocked path traversal attempt');
                return response()->json(['status' => false, 'message' => 'Invalid file'], 422);
            }

            // Use the EXISTING storage location (no migration / relocation).
            $fullPath = storage_path('app/public/' . ltrim($path, '/'));
            if (!file_exists($fullPath)) {
                $audit('FAILURE', 'File missing on disk');
                return response()->json(['status' => false, 'message' => 'File not found'], 404);
            }

            // Preserve existing behavior: log the download to the student's feed.
            $message = $type === 'resume'
                ? $firm->firm_name . ' downloaded your resume.'
                : $firm->firm_name . ' downloaded your marksheet.';
            DB::table('recruiter_actions')->insert([
                'student_id'     => (int) $studentId,
                'firm_id'        => $firm->id,
                'job_id'         => null,
                'application_id' => null,
                'visible_to'     => 'student',
                'action_type'    => $type . '_downloaded',
                'message'        => $message,
                'created_at'     => now(),
            ]);

            $audit('SUCCESS');
            return response()->download($fullPath);
        } catch (\Exception $e) {
            Log::error("Download Error: " . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ]);
        }
    }



    public function getNotifications(Request $request)
    {
        try {

            $authUser = $request->attributes->get('auth_user');

            $base = DB::table('notifications')->where('user_id', $authUser->id);

            // Unread badge count is independent of the current page.
            $unreadCount = (clone $base)->where('is_read', false)->count();

            // Single unified response shape for both callers: the bell dropdown
            // requests page 1 with per_page=25 and ignores the pagination meta;
            // the Notifications page uses the same fields to paginate.
            $page    = max(1, (int) $request->input('page', 1));
            $perPage = min(50, max(1, (int) $request->input('per_page', 25)));

            $total = (clone $base)->count();

            // Left-join the linked interview_invites row (+ candidate name) so an
            // "X accepted your interview invitation" notification can render an
            // inline "Schedule Interview" CTA (reusing the shared dialog + APIs)
            // and a decline notification can render the final rejected state — all
            // resolved from the invite's LIVE status. Non-invite notifications keep
            // NULL invite fields and render exactly as before.
            $notifications = $base
                ->leftJoin('interview_invites as ii', 'ii.id', '=', 'notifications.interview_invite_id')
                ->leftJoin('users as su', 'su.id', '=', 'ii.student_id')
                ->orderBy('notifications.created_at', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->select(
                    'notifications.*',
                    'ii.invite_status',
                    'ii.interview_status',
                    'ii.interview_date',
                    'ii.interview_mode',
                    // Extra invite fields → "Reschedule Interview" CTA + prefill for
                    // the shared ScheduleInterviewDialog. Backward-compatible.
                    'ii.interview_location',
                    'ii.interview_note',
                    'ii.student_interview_response',
                    'ii.reschedule_date',
                    'ii.student_id as candidate_id',
                    'su.name as candidate_name',
                )
                ->get();

            return response()->json([
                'status'       => true,
                'data'         => $notifications,
                'total'        => $total,
                'page'         => $page,
                'per_page'     => $perPage,
                'has_more'     => ($page * $perPage) < $total,
                'unread_count' => $unreadCount,
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Get Notifications Error: ' .
                    $e->getMessage()
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error'
            ]);
        }
    }
}
