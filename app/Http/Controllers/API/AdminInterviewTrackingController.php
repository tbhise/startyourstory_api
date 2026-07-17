<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * READ-ONLY admin view of every interview scheduled through the platform.
 *
 * Interviews live in TWO existing tables (no new tables — audited 2026-07-17):
 *   - applications       → job-application flow; position = jobs.title
 *   - interview_invites  → candidate-search direct flow; shown as "Direct Interview"
 *
 * Both are UNION ALL'd into one normalized shape with SQL CASE expressions so
 * status filters work server-side across the two flows:
 *
 *   approval_status  : pending | accepted | declined | reschedule_requested
 *     (applications.student_interview_response uses 'Accepted', invites use
 *      'Confirmed' — both normalize to 'accepted'; 'Rejected' → 'declined')
 *   interview_status : scheduled | completed | cancelled | rescheduled
 *     (applications flow has no completed state; invites map completed /
 *      cancelled / expired; reschedule_count > 0 → 'rescheduled')
 *
 * This controller only lists — all writes stay in JobsController /
 * InterviewInviteController (no duplicated business logic).
 */
class AdminInterviewTrackingController extends Controller
{
    private const PER_PAGE = 20;

    private function getAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /**
     * UNION ALL of both interview sources, wrapped as a derived table `t` so
     * filters/sorting/pagination run on the normalized columns.
     */
    private function baseQuery()
    {
        // Job-application interviews. jobs.firm_id = firm_profiles.id (project
        // convention — same join JobsController uses).
        $applications = DB::table('applications as a')
            ->join('users as su', 'su.id', '=', 'a.student_id')
            ->join('jobs as j', 'j.id', '=', 'a.job_id')
            ->leftJoin('firm_profiles as fp', 'fp.id', '=', 'j.firm_id')
            ->whereNotNull('a.interview_date')
            ->selectRaw("
                a.id as id,
                'application' as source,
                su.name as student_name,
                su.email as student_email,
                fp.firm_name as firm_name,
                j.title as position,
                0 as is_direct,
                a.interview_date as interview_date,
                a.interview_mode as interview_mode,
                CASE
                    WHEN a.student_interview_response = 'Accepted' THEN 'accepted'
                    WHEN a.student_interview_response = 'Rejected' THEN 'declined'
                    WHEN a.student_interview_response = 'Reschedule Requested' THEN 'reschedule_requested'
                    ELSE 'pending'
                END as approval_status,
                CASE
                    WHEN a.recruiter_status IN ('Rejected', 'Interview Expired', 'Withdrawn by Candidate') THEN 'cancelled'
                    WHEN COALESCE(a.interview_reschedule_count, 0) > 0 THEN 'rescheduled'
                    ELSE 'scheduled'
                END as interview_status,
                COALESCE(a.interview_reschedule_count, 0) as reschedule_count,
                COALESCE(a.interview_requested_at, a.applied_at) as scheduled_on
            ");

        // Direct (candidate-search) interviews. interview_invites.firm_id is a
        // firm_profiles.id; only rows with a scheduled date are interviews.
        $invites = DB::table('interview_invites as ii')
            ->join('users as su', 'su.id', '=', 'ii.student_id')
            ->leftJoin('firm_profiles as fp', 'fp.id', '=', 'ii.firm_id')
            ->whereNotNull('ii.interview_date')
            ->selectRaw("
                ii.id as id,
                'invite' as source,
                su.name as student_name,
                su.email as student_email,
                fp.firm_name as firm_name,
                NULL as position,
                1 as is_direct,
                ii.interview_date as interview_date,
                ii.interview_mode as interview_mode,
                CASE
                    WHEN ii.student_interview_response = 'Confirmed' THEN 'accepted'
                    WHEN ii.student_interview_response = 'Rejected' THEN 'declined'
                    WHEN ii.student_interview_response = 'Reschedule Requested' THEN 'reschedule_requested'
                    ELSE 'pending'
                END as approval_status,
                CASE
                    WHEN ii.interview_status = 'completed' THEN 'completed'
                    WHEN ii.interview_status IN ('cancelled', 'expired') THEN 'cancelled'
                    WHEN COALESCE(ii.reschedule_count, 0) > 0 THEN 'rescheduled'
                    ELSE 'scheduled'
                END as interview_status,
                COALESCE(ii.reschedule_count, 0) as reschedule_count,
                COALESCE(ii.scheduled_at, ii.created_at) as scheduled_on
            ");

        return DB::query()->fromSub($applications->unionAll($invites), 't');
    }

    /**
     * Shared list filters — all applied to the normalized derived table.
     */
    private function applyFilters($query, Request $request)
    {
        if ($from = $request->get('date_from')) {
            $query->whereDate('t.interview_date', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('t.interview_date', '<=', $to);
        }
        if (in_array($approval = $request->get('approval_status'), ['pending', 'accepted', 'declined', 'reschedule_requested'], true)) {
            $query->where('t.approval_status', $approval);
        }
        if (in_array($status = $request->get('interview_status'), ['scheduled', 'completed', 'cancelled', 'rescheduled'], true)) {
            $query->where('t.interview_status', $status);
        }
        if (($firm = trim((string) $request->get('firm', ''))) !== '') {
            $query->where('t.firm_name', 'like', "%{$firm}%");
        }
        if (($student = trim((string) $request->get('student', ''))) !== '') {
            $query->where(function ($q) use ($student) {
                $q->where('t.student_name', 'like', "%{$student}%")
                  ->orWhere('t.student_email', 'like', "%{$student}%");
            });
        }
        if (($position = trim((string) $request->get('position', ''))) !== '') {
            $query->where(function ($q) use ($position) {
                $q->where('t.position', 'like', "%{$position}%");
                // Let "direct" find candidate-search interviews (NULL position).
                if (stripos('direct interview', $position) !== false) {
                    $q->orWhere('t.is_direct', 1);
                }
            });
        }
        if ($scheduledDate = $request->get('scheduled_date')) {
            $query->whereDate('t.scheduled_on', $scheduledDate);
        }
        if (($search = trim((string) $request->get('search', ''))) !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('t.student_name', 'like', "%{$search}%")
                  ->orWhere('t.firm_name', 'like', "%{$search}%")
                  ->orWhere('t.position', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/interview-tracking
    | Filters: date_from, date_to, approval_status, interview_status, firm,
    |          student, position, scheduled_date, search, page
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $page = max(1, (int) $request->get('page', 1));

            $query = $this->applyFilters($this->baseQuery(), $request);

            $total = (clone $query)->count();

            $rows = $query
                ->orderByDesc('t.scheduled_on')
                ->orderByDesc('t.id')
                ->offset(($page - 1) * self::PER_PAGE)
                ->limit(self::PER_PAGE)
                ->get();

            $interviews = $rows->map(function ($r) {
                $ts = $r->interview_date ? strtotime($r->interview_date) : false;
                return [
                    'id'                 => (int) $r->id,
                    'source'             => $r->source,
                    'student_name'       => $r->student_name,
                    'student_email'      => $r->student_email,
                    'firm_name'          => $r->firm_name,
                    'position'           => $r->is_direct ? null : $r->position,
                    'is_direct'          => (bool) $r->is_direct,
                    'interview_date'     => $ts ? date('d M Y', $ts) : null,
                    'interview_time'     => $ts ? date('h:i A', $ts) : null,
                    'interview_datetime' => $r->interview_date,
                    'interview_mode'     => $r->interview_mode,
                    'approval_status'    => $r->approval_status,
                    'interview_status'   => $r->interview_status,
                    'reschedule_count'   => (int) $r->reschedule_count,
                    'created_on'         => $r->scheduled_on
                        ? date('d M Y, h:i A', strtotime($r->scheduled_on))
                        : null,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Interviews fetched',
                'data'    => [
                    'interviews' => $interviews,
                    'total'      => $total,
                    'page'       => $page,
                    'last_page'  => max(1, (int) ceil($total / self::PER_PAGE)),
                    'has_more'   => ($page * self::PER_PAGE) < $total,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminInterviewTrackingController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/interview-tracking/stats — headline counts for stat cards
    |--------------------------------------------------------------------------
    */
    public function stats(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $counts = $this->baseQuery()
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(t.interview_status IN ('scheduled', 'rescheduled')) as scheduled")
                ->selectRaw("SUM(t.approval_status = 'pending' AND t.interview_status IN ('scheduled', 'rescheduled')) as awaiting_response")
                ->selectRaw("SUM(t.interview_status IN ('scheduled', 'rescheduled') AND t.interview_date >= NOW()) as upcoming")
                ->first();

            return response()->json([
                'status' => true,
                'data'   => [
                    'total'             => (int) ($counts->total ?? 0),
                    'scheduled'         => (int) ($counts->scheduled ?? 0),
                    'awaiting_response' => (int) ($counts->awaiting_response ?? 0),
                    'upcoming'          => (int) ($counts->upcoming ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminInterviewTrackingController@stats: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
