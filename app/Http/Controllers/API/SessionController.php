<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SessionController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  GET /sessions                                                        */
    /*  Returns all active sessions for the authenticated user.             */
    /* ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        try {
            $user         = $request->attributes->get('auth_user');
            $currentToken = $request->cookie('auth_token');

            $sessions = DB::table('user_sessions')
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity_at')
                ->get()
                ->map(function ($s) use ($currentToken) {
                    return [
                        'id'               => $s->id,
                        'device_type'      => $s->device_type,
                        'browser'          => $s->browser,
                        'os'               => $s->os,
                        'ip_address'       => $s->ip_address,
                        'location'         => $s->location,
                        'last_activity_at' => $s->last_activity_at,
                        'created_at'       => $s->created_at,
                        'is_current'       => $s->token === $currentToken,
                    ];
                });

            return response()->json([
                'status' => true,
                'data'   => ['sessions' => $sessions],
            ]);
        } catch (\Exception $e) {
            Log::error('Sessions index error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  DELETE /sessions/{id}                                               */
    /*  Logs out a specific device (cannot revoke the current session).     */
    /* ------------------------------------------------------------------ */
    public function destroy(Request $request, int $id)
    {
        try {
            $user = $request->attributes->get('auth_user');

            $session = DB::table('user_sessions')
                ->where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$session) {
                return response()->json(['status' => false, 'message' => 'Session not found.'], 404);
            }

            $currentToken = $request->cookie('auth_token');
            if ($session->token === $currentToken) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Use the logout button to end your current session.',
                ], 400);
            }

            DB::table('user_sessions')->where('id', $id)->delete();

            // If this revoked token happens to still be in users.api_token, clear it.
            DB::table('users')
                ->where('id', $user->id)
                ->where('api_token', $session->token)
                ->update(['api_token' => null, 'token_expires_at' => null, 'updated_at' => now()]);

            return response()->json([
                'status'  => true,
                'message' => 'Device logged out successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Session destroy error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  DELETE /sessions/all                                                */
    /*  Logs out every device except the one making this request.           */
    /* ------------------------------------------------------------------ */
    public function destroyAll(Request $request)
    {
        try {
            $user         = $request->attributes->get('auth_user');
            $currentToken = $request->cookie('auth_token');

            DB::table('user_sessions')
                ->where('user_id', $user->id)
                ->where('token', '!=', $currentToken)
                ->delete();

            return response()->json([
                'status'  => true,
                'message' => 'All other devices have been logged out.',
            ]);
        } catch (\Exception $e) {
            Log::error('Session destroyAll error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  GET /login-history                                                  */
    /*  Returns the last 50 login events for the authenticated user.        */
    /* ------------------------------------------------------------------ */
    public function loginHistory(Request $request)
    {
        try {
            $user = $request->attributes->get('auth_user');

            $history = DB::table('login_history')
                ->where('user_id', $user->id)
                ->orderByDesc('logged_in_at')
                ->limit(50)
                ->get()
                ->map(fn($h) => [
                    'id'           => $h->id,
                    'ip_address'   => $h->ip_address,
                    'device_type'  => $h->device_type,
                    'browser'      => $h->browser,
                    'os'           => $h->os,
                    'location'     => $h->location,
                    'logged_in_at' => $h->logged_in_at,
                ]);

            return response()->json([
                'status' => true,
                'data'   => ['history' => $history],
            ]);
        } catch (\Exception $e) {
            Log::error('Login history error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
