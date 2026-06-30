<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * READ-ONLY view of the firm/student business activity log (activity_logs).
 *
 * Rows are written asynchronously by ActivityTracker / LogActivityJob from
 * within the operations they record; this controller only lists + filters them.
 * There is intentionally NO store/update/delete — the log is append-only.
 *
 * activity_logs.actor_id is a users.id. The acting account's display name is the
 * firm_name (for firms) or the user's name (for students); both join off users.
 */
class AdminActivityTrackerController extends Controller
{
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
     * Base query joining the actor's account (users) + firm profile so the
     * display name / email are available for both listing and search.
     */
    private function baseQuery()
    {
        return DB::table('activity_logs as al')
            ->leftJoin('users as u', 'u.id', '=', 'al.actor_id')
            ->leftJoin('firm_profiles as fp', 'fp.user_id', '=', 'al.actor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/activity-tracker
    | Filters: actor_type, action_type, date_from, date_to, search, page
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $page    = max(1, (int) $request->get('page', 1));
            $perPage = 50;

            $query = $this->applyFilters($this->baseQuery(), $request);

            $total = (clone $query)->count();

            $rows = $query
                ->orderByDesc('al.id')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get([
                    'al.id',
                    'al.actor_type',
                    'al.actor_id',
                    'al.action_type',
                    'al.meta',
                    'al.created_at',
                    'u.name as user_name',
                    'u.email as actor_email',
                    'fp.firm_name as firm_name',
                ]);

            $logs = $rows->map(function ($r) {
                // Firms display by firm_name; students by user name. Fall back gracefully.
                $actorName = $r->actor_type === 'firm'
                    ? ($r->firm_name ?: $r->user_name)
                    : $r->user_name;

                return [
                    'id'          => (int) $r->id,
                    'actor_type'  => $r->actor_type,
                    'actor_id'    => (int) $r->actor_id,
                    'actor_name'  => $actorName ?: ('User #' . $r->actor_id),
                    'actor_email' => $r->actor_email,
                    'action_type' => $r->action_type,
                    'meta'        => $r->meta ? json_decode($r->meta, true) : null,
                    'created_at'  => $r->created_at,
                ];
            });

            return response()->json([
                'status'  => true,
                'message' => 'Activity logs fetched',
                'data'    => [
                    'logs'      => $logs,
                    'total'     => $total,
                    'page'      => $page,
                    'last_page' => max(1, (int) ceil($total / $perPage)),
                    'has_more'  => ($page * $perPage) < $total,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminActivityTrackerController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/activity-tracker/stats
    | Lightweight headline counts for the stat cards.
    |--------------------------------------------------------------------------
    */
    public function stats(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $counts = DB::table('activity_logs')
                ->selectRaw('COUNT(*) as total')
                ->selectRaw("SUM(actor_type = 'firm') as firm")
                ->selectRaw("SUM(actor_type = 'student') as student")
                ->selectRaw('SUM(DATE(created_at) = CURDATE()) as today')
                ->first();

            return response()->json([
                'status' => true,
                'data'   => [
                    'total'   => (int) ($counts->total ?? 0),
                    'firm'    => (int) ($counts->firm ?? 0),
                    'student' => (int) ($counts->student ?? 0),
                    'today'   => (int) ($counts->today ?? 0),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminActivityTrackerController@stats: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Apply the shared list filters (actor type, action, date range, search).
     */
    private function applyFilters($query, Request $request)
    {
        if (in_array($actor = $request->get('actor_type'), ['firm', 'student'], true)) {
            $query->where('al.actor_type', $actor);
        }
        if ($action = $request->get('action_type')) {
            $query->where('al.action_type', $action);
        }
        if ($from = $request->get('date_from')) {
            $query->whereDate('al.created_at', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('al.created_at', '<=', $to);
        }
        if (($search = trim((string) $request->get('search', ''))) !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('u.name', 'like', "%{$search}%")
                  ->orWhere('fp.firm_name', 'like', "%{$search}%")
                  ->orWhere('u.email', 'like', "%{$search}%")
                  ->orWhere('al.action_type', 'like', "%{$search}%");
            });
        }
        return $query;
    }
}
