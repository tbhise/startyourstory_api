<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * FCM device-token registration for STUDENTS and FIRMS.
 *
 * Separate from AdminNotificationController@registerFcmToken on purpose —
 * user tokens live in user_fcm_tokens and are keyed to users.id, so the two
 * push audiences can never cross. Routes sit inside ApiAuthMiddleware
 * (auth_token cookie), mirroring the admin endpoints' contract.
 */
class UserPushController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | POST /fcm/token — register (or refresh) this user's device token
    |--------------------------------------------------------------------------
    */
    public function registerToken(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $validator = Validator::make($request->all(), [
            'token'       => 'required|string|max:512',
            'device_info' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $token      = $request->input('token');
            $deviceInfo = $request->input('device_info', substr((string) $request->userAgent(), 0, 255));
            $now        = now();

            // Upsert on the unique token: re-registering the same device (or a
            // device whose session moved to another user) reassigns + refreshes.
            $existing = DB::table('user_fcm_tokens')->where('token', $token)->first();
            if ($existing) {
                DB::table('user_fcm_tokens')->where('id', $existing->id)->update([
                    'user_id'        => $user->id,
                    'device_info'    => $deviceInfo,
                    'last_active_at' => $now,
                    'updated_at'     => $now,
                ]);
            } else {
                DB::table('user_fcm_tokens')->insert([
                    'user_id'        => $user->id,
                    'token'          => $token,
                    'device_info'    => $deviceInfo,
                    'last_active_at' => $now,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ]);
            }

            return response()->json(['status' => true, 'message' => 'Device registered for notifications']);
        } catch (\Throwable $e) {
            Log::error('UserPushController@registerToken: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE /fcm/token — unregister this device (e.g. on logout)
    |--------------------------------------------------------------------------
    */
    public function deleteToken(Request $request)
    {
        $user = $request->attributes->get('auth_user');
        if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $token = $request->input('token');
        if (!$token) {
            return response()->json(['status' => false, 'message' => 'Token is required'], 422);
        }

        try {
            DB::table('user_fcm_tokens')
                ->where('token', $token)
                ->where('user_id', $user->id)
                ->delete();

            return response()->json(['status' => true, 'message' => 'Device unregistered']);
        } catch (\Throwable $e) {
            Log::error('UserPushController@deleteToken: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
