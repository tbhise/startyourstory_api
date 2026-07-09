<?php

namespace App\Http\Controllers\API;

use App\Helpers\AuthHelper;
use App\Helpers\FreeActionsHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Firm Activity Center (Phase 1 — display only).
 *
 * Read-side of the firm-facing activity feed: the Interview Credits summary
 * (reuses FreeActionsHelper — the exact numbers the free-action gate already
 * enforces, no new business logic) plus the firm_activities timeline written
 * by FirmActivityHelper at existing action sites.
 */
class FirmActivityController extends Controller
{
    public function activityCenter(Request $request)
    {
        try {
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }
            if ($user->role !== 'firm') {
                return response()->json(['status' => false, 'message' => 'Only firm accounts can view the Activity Center'], 403);
            }
            $firm = DB::table('firm_profiles')->where('user_id', $user->id)->first();
            if (!$firm) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            // Interview Credits — same source the scheduling gate uses.
            $credits = FreeActionsHelper::getStatus($firm->id);

            $perPage = min(50, max(1, (int) $request->input('per_page', 20)));
            $page    = max(1, (int) $request->input('page', 1));

            $total = (int) DB::table('firm_activities')
                ->where('firm_id', $firm->id)
                ->count();

            // Left-join the linked interview_invites row (+ candidate name) so the
            // page can render the invite's LIVE state — a "Schedule Interview" CTA
            // while accepted-but-not-scheduled, or the final rejected state — from
            // the same source of truth the ScheduleInterviewDialog already writes.
            // Rows with no interview_invite_id simply return NULL invite fields.
            $activities = DB::table('firm_activities as fa')
                ->leftJoin('interview_invites as ii', 'ii.id', '=', 'fa.interview_invite_id')
                ->leftJoin('users as su', 'su.id', '=', 'ii.student_id')
                ->where('fa.firm_id', $firm->id)
                ->orderBy('fa.created_at', 'desc')
                ->orderBy('fa.id', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->select(
                    'fa.id',
                    'fa.action',
                    'fa.description',
                    'fa.created_at',
                    'fa.interview_invite_id',
                    'ii.invite_status',
                    'ii.interview_status',
                    'ii.interview_date',
                    'ii.interview_mode',
                    'su.name as candidate_name',
                )
                ->get()
                ->map(fn ($row) => [
                    'id'                  => (string) $row->id,
                    'action'              => $row->action,
                    'description'         => $row->description,
                    'created_at'          => $row->created_at,
                    'interview_invite_id' => $row->interview_invite_id ? (string) $row->interview_invite_id : null,
                    'invite_status'       => $row->invite_status,
                    'interview_status'    => $row->interview_status,
                    'interview_date'      => $row->interview_date,
                    'interview_mode'      => $row->interview_mode,
                    'candidate_name'      => $row->candidate_name,
                ])
                ->values();

            return response()->json([
                'status' => true,
                'data'   => [
                    'credits'    => $credits,
                    'activities' => $activities,
                    'pagination' => [
                        'page'     => $page,
                        'per_page' => $perPage,
                        'total'    => $total,
                        'has_more' => $page * $perPage < $total,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Firm Activity Center API Error', [
                'message' => $e->getMessage(),
                'line'    => $e->getLine(),
                'file'    => $e->getFile(),
            ]);
            return response()->json([
                'status'  => false,
                'message' => 'Unexpected server error while fetching activity center.',
            ], 500);
        }
    }
}
