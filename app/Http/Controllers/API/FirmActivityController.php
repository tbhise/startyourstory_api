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

            $activities = DB::table('firm_activities')
                ->where('firm_id', $firm->id)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get()
                ->map(fn ($row) => [
                    'id'          => (string) $row->id,
                    'action'      => $row->action,
                    'description' => $row->description,
                    'created_at'  => $row->created_at,
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
