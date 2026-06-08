<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->cookie('auth_token');

        if (!$token) {
            return response()->json(['status' => false, 'message' => 'Token missing'], 401);
        }

        // ── Step 1: look up the session in user_sessions (new architecture) ──
        $session = DB::table('user_sessions')
            ->where('token', $token)
            ->first();

        if ($session) {
            // Check per-session expiry
            if ($session->expires_at && now()->gt($session->expires_at)) {
                DB::table('user_sessions')->where('token', $token)->delete();
                DB::table('users')
                    ->where('id', $session->user_id)
                    ->where('api_token', $token)
                    ->update(['api_token' => null, 'token_expires_at' => null]);

                return response()->json(['status' => false, 'message' => 'Session expired'], 401);
            }

            $user = DB::table('users')
                ->where('id', $session->user_id)
                ->where('is_deleted', false)
                ->first();
        } else {
            // ── Step 2: fall back to legacy users.api_token ──────────────────
            // Handles tokens issued before user_sessions was introduced.
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();

            if ($user && $user->token_expires_at && now()->gt($user->token_expires_at)) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['api_token' => null, 'token_expires_at' => null]);

                return response()->json(['status' => false, 'message' => 'Session expired'], 401);
            }
        }

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->attributes->set('auth_user', $user);

        // Keep last_activity_at fresh for the session tracker (best-effort, no-throw)
        if ($session) {
            DB::table('user_sessions')
                ->where('token', $token)
                ->update(['last_activity_at' => now(), 'updated_at' => now()]);
        }

        return $next($request);
    }
}
