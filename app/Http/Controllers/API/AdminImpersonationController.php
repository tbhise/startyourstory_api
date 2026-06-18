<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Admin "Login as User" (impersonation).
 *
 * Design goals (do NOT regress):
 *  - The admin's own session (admin_token) is NEVER modified — exit always returns
 *    the admin safely to the panel, even after browser close.
 *  - The target user's real sessions / users.api_token are NEVER touched. We mint a
 *    SEPARATE, short-lived user_sessions row (is_impersonation = 1) and set it as the
 *    auth_token cookie. The real user can keep using their account in parallel.
 *  - Only an active super_admin can start an impersonation.
 *  - The session is flagged so BlockImpersonationWrites can enforce read-only.
 *
 * Both routes live under /admin/* so AdminAuthMiddleware already requires a valid
 * admin_token; here we additionally require role = super_admin.
 */
class AdminImpersonationController extends Controller
{
    /** Resolve the acting admin from admin_token; must be active + super_admin. */
    private function getSuperAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) {
            return null;
        }
        $admin = DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
        if (!$admin || ($admin->role ?? null) !== 'super_admin') {
            return null;
        }
        return $admin;
    }

    /** Resolve the acting admin from admin_token (any active admin) — used by stop(). */
    private function getActiveAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) {
            return null;
        }
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    /**
     * POST /admin/impersonate/{userId}
     * Start impersonating a student or firm.
     */
    public function start(Request $request, $userId): JsonResponse
    {
        $admin = $this->getSuperAdmin($request);
        if (!$admin) {
            return response()->json([
                'status'  => false,
                'message' => 'Only a super admin can use Login as User.',
            ], 403);
        }

        $target = DB::table('users')
            ->where('id', (int) $userId)
            ->where('is_deleted', false)
            ->first();

        if (!$target) {
            return response()->json(['status' => false, 'message' => 'User not found.'], 404);
        }
        if (!in_array($target->role, ['student', 'firm'], true)) {
            return response()->json([
                'status'  => false,
                'message' => 'Only student and firm accounts can be impersonated.',
            ], 422);
        }

        try {
            // End any existing active impersonation for THIS admin first (one at a time).
            $this->endActiveImpersonationsForAdmin($admin->id);

            $token     = base64_encode(Str::random(40));   // same format as user login tokens
            $expiresAt = now()->addHour();                  // short-lived impersonation
            $now       = now();
            $ip        = $request->ip();

            // Standalone session row — never overwrites users.api_token.
            DB::table('user_sessions')->insert([
                'user_id'          => $target->id,
                'token'            => $token,
                'device_type'      => 'desktop',
                'browser'          => 'Admin Impersonation',
                'os'               => null,
                'ip_address'       => $ip,
                'location'         => 'Admin Impersonation',
                'is_impersonation' => true,
                'impersonated_by'  => $admin->id,
                'expires_at'       => $expiresAt,
                'last_activity_at' => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            DB::table('admin_impersonation_sessions')->insert([
                'admin_id'       => $admin->id,
                'admin_name'     => $admin->name,
                'target_user_id' => $target->id,
                'target_role'    => $target->role,
                'token'          => $token,
                'ip_address'     => $ip,
                'login_time'     => $now,
                'logout_time'    => null,
                'created_at'     => $now,
            ]);

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::IMPERSONATION_STARTED,
                $target->role,
                $target->id,
                "Started impersonating {$target->role} #{$target->id} ({$target->email}).",
                $request
            );

            return response()
                ->json([
                    'status'  => true,
                    'message' => 'Impersonation started.',
                    'data'    => [
                        'target' => [
                            'id'    => $target->id,
                            'name'  => $target->name,
                            'email' => $target->email,
                            'role'  => $target->role,
                        ],
                        // Where the SPA should land for this role.
                        'redirect' => $target->role === 'firm' ? '/firm-dashboard' : '/dashboard',
                    ],
                ])
                ->cookie(
                    'auth_token',
                    $token,
                    60,          // 1 hour in minutes
                    '/',
                    null,
                    false,       // secure (set true on HTTPS prod)
                    false,       // httpOnly — mirrors normal user auth_token
                    false,
                    'Lax'
                );
        } catch (\Throwable $e) {
            Log::error('Impersonation start error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * POST /admin/impersonate/stop
     * Exit impersonation. Clears only auth_token; admin_token is left intact so the
     * admin is still logged into the panel.
     */
    public function stop(Request $request): JsonResponse
    {
        $token = $request->cookie('auth_token');

        try {
            if ($token) {
                $session = DB::table('user_sessions')
                    ->where('token', $token)
                    ->where('is_impersonation', true)
                    ->first();

                if ($session) {
                    $imp = DB::table('admin_impersonation_sessions')
                        ->where('token', $token)
                        ->whereNull('logout_time')
                        ->first();

                    DB::table('admin_impersonation_sessions')
                        ->where('token', $token)
                        ->whereNull('logout_time')
                        ->update(['logout_time' => now()]);

                    DB::table('user_sessions')->where('token', $token)->delete();

                    $admin = $this->getActiveAdmin($request);
                    AdminActivityLogger::log(
                        $admin ?: (object) ['id' => $session->impersonated_by, 'name' => $imp->admin_name ?? null],
                        AdminActivityLogger::IMPERSONATION_ENDED,
                        $session->is_impersonation ? ($imp->target_role ?? 'user') : 'user',
                        $session->user_id,
                        "Ended impersonation of user #{$session->user_id}.",
                        $request
                    );
                }
            }

            return response()
                ->json(['status' => true, 'message' => 'Impersonation ended.'])
                ->cookie('auth_token', '', -1, '/', null, false, false, false, 'Lax');
        } catch (\Throwable $e) {
            Log::error('Impersonation stop error: ' . $e->getMessage());
            // Still clear the cookie so the admin is never stuck.
            return response()
                ->json(['status' => true, 'message' => 'Impersonation ended.'])
                ->cookie('auth_token', '', -1, '/', null, false, false, false, 'Lax');
        }
    }

    /** Close any open impersonation sessions started by this admin. */
    private function endActiveImpersonationsForAdmin(int $adminId): void
    {
        $open = DB::table('admin_impersonation_sessions')
            ->where('admin_id', $adminId)
            ->whereNull('logout_time')
            ->get();

        foreach ($open as $row) {
            DB::table('user_sessions')
                ->where('token', $row->token)
                ->where('is_impersonation', true)
                ->delete();
        }

        DB::table('admin_impersonation_sessions')
            ->where('admin_id', $adminId)
            ->whereNull('logout_time')
            ->update(['logout_time' => now()]);
    }
}
