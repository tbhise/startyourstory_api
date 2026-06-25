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

        // Auth is user_sessions-only. Look up the session by token; no legacy
        // users.api_token fallback.
        $session = DB::table('user_sessions')
            ->where('token', $token)
            ->first();

        if (!$session) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Per-session expiry
        if ($session->expires_at && now()->gt($session->expires_at)) {
            DB::table('user_sessions')->where('token', $token)->delete();
            return response()->json(['status' => false, 'message' => 'Session expired'], 401);
        }

        $user = DB::table('users')
            ->where('id', $session->user_id)
            ->where('is_deleted', false)
            ->first();

        if (!$user) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $request->attributes->set('auth_user', $user);
        // Bridge for framework features that resolve the user via $request->user()
        // (e.g. broadcasting channel authorization). Additive — existing code that
        // reads the 'auth_user' attribute is unaffected.
        $request->setUserResolver(fn () => $user);

        // Keep last_activity_at fresh for the session tracker (best-effort, no-throw)
        DB::table('user_sessions')
            ->where('token', $token)
            ->update(['last_activity_at' => now(), 'updated_at' => now()]);

        return $next($request);
    }
}
