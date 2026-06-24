<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Centralized user-authentication resolver.
 *
 * Single source of truth for "who is the logged-in user on this request".
 * Resolution is user_sessions-only — it NEVER reads users.api_token.
 *
 *  1. If ApiAuthMiddleware already resolved the user (routes inside the
 *     middleware group), reuse that `auth_user` request attribute.
 *  2. Otherwise resolve the `auth_token` cookie against user_sessions
 *     (this covers optional-auth / public routes that sit outside the
 *     middleware group, e.g. /error-logs, PhonePe firm payments,
 *     /premium-requests).
 *
 * Impersonation works transparently: an admin impersonation session is a
 * row in user_sessions, so the impersonated user is resolved here too.
 */
class AuthHelper
{
    /**
     * Resolve the authenticated user row (stdClass) for a request, or null.
     * The returned row is guaranteed to be a non-deleted user.
     */
    public static function resolveUser(Request $request): ?object
    {
        $pre = $request->attributes->get('auth_user');
        if ($pre) {
            return $pre;
        }

        $userId = self::resolveUserId($request);
        if (!$userId) {
            return null;
        }

        return DB::table('users')
            ->where('id', $userId)
            ->where('is_deleted', false)
            ->first();
    }

    /**
     * Resolve just the authenticated user's id (cheap), or null.
     * Expired sessions are deleted and treated as unauthenticated.
     */
    public static function resolveUserId(Request $request): ?int
    {
        $pre = $request->attributes->get('auth_user');
        if ($pre) {
            return (int) $pre->id;
        }

        $token = $request->cookie('auth_token');
        if (!$token) {
            return null;
        }

        $session = DB::table('user_sessions')->where('token', $token)->first();
        if (!$session) {
            return null;
        }

        if ($session->expires_at && now()->gt($session->expires_at)) {
            DB::table('user_sessions')->where('token', $token)->delete();
            return null;
        }

        return (int) $session->user_id;
    }
}
