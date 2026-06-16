<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * READ-ONLY view of the admin audit trail (admin_activity_logs).
 *
 * There is intentionally NO store/update/delete here — activity logs are written
 * only by AdminActivityLogger (from within the actions they record) and are
 * retained indefinitely. This controller only lists + filters them.
 */
class AdminActivityLogController extends Controller
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

    /*
    |--------------------------------------------------------------------------
    | GET /admin/activity-logs
    | Filters: admin_id, action_type, entity_type, date_from, date_to, search, page
    |--------------------------------------------------------------------------
    */
    public function index(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $page    = max(1, (int) $request->get('page', 1));
            $perPage = 50;

            $query = DB::table('admin_activity_logs');

            if (($adminId = $request->get('admin_id')) !== null && $adminId !== '') {
                $query->where('admin_id', (int) $adminId);
            }
            if ($action = $request->get('action_type')) {
                $query->where('action_type', $action);
            }
            if ($entity = $request->get('entity_type')) {
                $query->where('entity_type', $entity);
            }
            if ($from = $request->get('date_from')) {
                $query->whereDate('created_at', '>=', $from);
            }
            if ($to = $request->get('date_to')) {
                $query->whereDate('created_at', '<=', $to);
            }
            if (($search = trim((string) $request->get('search', ''))) !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                      ->orWhere('admin_name', 'like', "%{$search}%")
                      ->orWhere('entity_id', 'like', "%{$search}%");
                });
            }

            $total = (clone $query)->count();
            $rows  = $query
                ->orderByDesc('id')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Activity logs fetched',
                'data'    => [
                    'logs'     => $rows,
                    'total'    => $total,
                    'page'     => $page,
                    'has_more' => ($page * $perPage) < $total,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminActivityLogController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/activity-logs/filters
    | Distinct values to populate the filter dropdowns (admins, actions, entities).
    |--------------------------------------------------------------------------
    */
    public function filters(Request $request): JsonResponse
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            // Admins that have actually performed logged actions.
            $admins = DB::table('admin_activity_logs')
                ->select('admin_id', 'admin_name')
                ->whereNotNull('admin_id')
                ->groupBy('admin_id', 'admin_name')
                ->orderBy('admin_name')
                ->get();

            $actionTypes = DB::table('admin_activity_logs')
                ->distinct()
                ->orderBy('action_type')
                ->pluck('action_type');

            $entityTypes = DB::table('admin_activity_logs')
                ->distinct()
                ->orderBy('entity_type')
                ->pluck('entity_type');

            return response()->json([
                'status' => true,
                'data'   => [
                    'admins'       => $admins,
                    'action_types' => $actionTypes,
                    'entity_types' => $entityTypes,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminActivityLogController@filters: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
