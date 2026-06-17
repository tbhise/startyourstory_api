<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Centralized admin authentication (C1 fix).
 *
 * Registered on the global "api" middleware group, but it ENFORCES only on admin
 * endpoints (path "admin/*", excluding the auth-bootstrap routes). This guarantees
 * every current AND future /admin/* route requires a valid, active admin session
 * without depending on each controller remembering to check. Existing per-controller
 * admin_token checks are retained as defense-in-depth.
 *
 * Auth model matches the rest of the app: admin_token cookie -> admin_users.api_token,
 * and the admin must be is_active = true (mirrors AdminController::login / the various
 * getAdmin() helpers).
 */
class AdminAuthMiddleware
{
    /** Admin paths that must stay reachable without an existing admin session. */
    private const EXEMPT = ['admin/login', 'admin/me', 'admin/logout'];

    public function handle(Request $request, Closure $next)
    {
        // Never interfere with CORS preflight.
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        // Normalize the path and strip the leading "api/" route prefix if present.
        $path = ltrim($request->path(), '/');
        if (str_starts_with($path, 'api/')) {
            $path = substr($path, 4);
        }

        // Only guard admin endpoints; every other request passes straight through.
        if ($path !== 'admin' && !str_starts_with($path, 'admin/')) {
            return $next($request);
        }
        if (in_array($path, self::EXEMPT, true)) {
            return $next($request);
        }

        $token = $request->cookie('admin_token');
        if (!$token) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $admin = DB::table('admin_users')->where('api_token', $token)->first();
        if (!$admin) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        if (!$admin->is_active) {
            return response()->json(['status' => false, 'message' => 'Account is inactive'], 403);
        }

        // Expose the resolved admin for controllers/refactors that want it.
        $request->attributes->set('admin_user', $admin);

        return $next($request);
    }
}
