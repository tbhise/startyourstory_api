<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\AdminFcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Admin notification feed (Phase 1 — storage + read state only; no UI here).
 * Auth follows the existing admin pattern: admin_token cookie -> admin_users.
 */
class AdminNotificationController extends Controller
{
    private function getAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return \Illuminate\Support\Facades\DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/notifications
    | Query: ?type=, ?is_read=0|1, ?page= (paginated, 20/page)
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            // created_at DESC, then id DESC as a tiebreaker so notifications created
            // in the same second always surface newest-first (stable ordering).
            $query = AdminNotification::query()->orderByDesc('created_at')->orderByDesc('id');

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }
            if ($request->filled('is_read')) {
                $query->where('is_read', $request->boolean('is_read'));
            }
            if ($request->filled('search')) {
                $s = '%' . trim($request->input('search')) . '%';
                $query->where(function ($q) use ($s) {
                    $q->where('title', 'like', $s)->orWhere('message', 'like', $s);
                });
            }

            $notifications = $query->paginate(20);

            return response()->json([
                'status' => true,
                'data'   => [
                    'notifications' => $notifications->items(),
                    'total'         => $notifications->total(),
                    'page'          => $notifications->currentPage(),
                    'per_page'      => $notifications->perPage(),
                    'has_more'      => $notifications->hasMorePages(),
                    'unread_count'  => AdminNotification::where('is_read', false)->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminNotificationController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/notifications/unread-count
    |--------------------------------------------------------------------------
    */
    public function unreadCount(Request $request)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            return response()->json([
                'status' => true,
                'data'   => ['unread_count' => AdminNotification::where('is_read', false)->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminNotificationController@unreadCount: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/notifications/{id}/read
    |--------------------------------------------------------------------------
    */
    public function markRead(Request $request, $id)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $notification = AdminNotification::find($id);
            if (!$notification) {
                return response()->json(['status' => false, 'message' => 'Notification not found'], 404);
            }

            if (!$notification->is_read) {
                $notification->update(['is_read' => true, 'read_at' => now()]);
            }

            return response()->json([
                'status'  => true,
                'message' => 'Notification marked as read',
                'data'    => ['unread_count' => AdminNotification::where('is_read', false)->count()],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminNotificationController@markRead: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/notifications/read-all
    |--------------------------------------------------------------------------
    */
    public function markAllRead(Request $request)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $updated = AdminNotification::where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now(), 'updated_at' => now()]);

            return response()->json([
                'status'  => true,
                'message' => "Marked {$updated} notification(s) as read",
                'data'    => ['unread_count' => 0],
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminNotificationController@markAllRead: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/fcm/token — register (or refresh) this admin's device token
    |--------------------------------------------------------------------------
    | Security: only an authenticated admin can register a token, and it is
    | bound to that admin's id — students/firms can never receive admin pushes.
    */
    public function registerFcmToken(Request $request)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $validator = Validator::make($request->all(), [
            'token'       => 'required|string|max:512',
            'device_info' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            // updateOrCreate on the unique token: re-registering the same device
            // (or a device that moved to another admin) reassigns + refreshes it.
            AdminFcmToken::updateOrCreate(
                ['token' => $request->input('token')],
                [
                    'admin_user_id'  => $admin->id,
                    'device_info'    => $request->input('device_info', substr((string) $request->userAgent(), 0, 255)),
                    'last_active_at' => now(),
                ]
            );

            return response()->json(['status' => true, 'message' => 'Device registered for notifications']);
        } catch (\Throwable $e) {
            Log::error('AdminNotificationController@registerFcmToken: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /admin/fcm/token — unregister this device (e.g. on logout)
    |--------------------------------------------------------------------------
    */
    public function deleteFcmToken(Request $request)
    {
        $admin = $this->getAdmin($request);
        if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $token = $request->input('token');
        if (!$token) {
            return response()->json(['status' => false, 'message' => 'Token is required'], 422);
        }

        try {
            AdminFcmToken::where('token', $token)->where('admin_user_id', $admin->id)->delete();
            return response()->json(['status' => true, 'message' => 'Device unregistered']);
        } catch (\Throwable $e) {
            Log::error('AdminNotificationController@deleteFcmToken: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
