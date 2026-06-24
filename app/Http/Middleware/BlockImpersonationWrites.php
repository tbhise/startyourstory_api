<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Read-only guard for admin impersonation ("Login as User") sessions.
 *
 * Registered globally on the "api" group (like AdminAuthMiddleware) but it is a
 * NO-OP for everyone except an active impersonation session — i.e. it only acts
 * when the auth_token cookie maps to a user_sessions row with is_impersonation = 1.
 * Normal student/firm/admin requests pass straight through untouched.
 *
 * Enforcement (deny-list, per product decision):
 *   - Any mutating HTTP verb (PUT / PATCH / DELETE) is always a write here → blocked.
 *   - POST endpoints are overloaded for reads in this API, so only an explicit
 *     deny-list of sensitive POST paths is blocked; all other reads keep working.
 *
 * Returns 403 with a clear message; never throws.
 */
class BlockImpersonationWrites
{
    /**
     * Sensitive paths (api/ prefix stripped). `*` is a wildcard segment matcher
     * (Str::is). Matched for POST requests; PUT/PATCH/DELETE are blocked regardless.
     */
    private const BLOCKED_POST = [
        // Profile / account writes
        'updateProfile',
        'updateProfileImage',
        'firm_profile_update',
        'auth/change-password',
        'account/request-deletion',
        'student/*',                 // directory-visibility, report-profile, premium-request
        'students/*/track-recruiter-action',
        'dismiss-apply-limit-modal',

        // Job application / saved jobs / interview responses
        'jobs/*/apply',
        'jobs/*/save',
        'applications/*',            // respondInterview, updateStatus, schedule-interview, accept-reschedule

        // Firm job management
        'createJob',
        'updateJob/*',
        'deleteFirmJob/*',
        'updateJobStatus/*',

        // Resume / candidate file download (firm side)
        'downloadFile',

        // Student resume builder — editing + downloading are blocked in
        // impersonation (view only). 'resume/preview-html' stays allowed so the
        // admin can still SEE the resume; DELETE /resume is blocked by the verb rule.
        'resume',                    // POST /resume = saveResume (edit)
        'resume/pdf',                // POST /resume/pdf = downloadPdf (download)

        // Centralized payout details (referral earners + creators) — add/edit blocked.
        'payout-details',

        // Wallet / premium / payments
        'wallet/recharge',
        'wallet/recharge/*',
        'premium-requests',
        'payments/*',

        // Messaging (all POST messaging actions are writes)
        'messaging/*',
        'mark-read',

        // Creator marketplace writes (POST read endpoints like my-projects/my-bids are NOT listed)
        'creator-marketplace/projects',
        'creator-marketplace/projects/*/update',
        'creator-marketplace/projects/*/close',
        'creator-marketplace/bids/*',
        'creator-marketplace/engagements/*/brief',
        'creator-marketplace/engagements/*/submit-work',
        'creator-marketplace/engagements/*/request-revision',
        'creator-marketplace/engagements/*/approve',
        'creator-marketplace/engagements/*/payment/*',
        'creator-marketplace/bank-details',
        'creator-marketplace/notifications/*',

        // Free content credits (firm)
        'free-content/requests',
    ];

    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $token = $request->cookie('auth_token');
        if (!$token) {
            return $next($request);
        }

        // Only act for an active impersonation session. One cheap indexed lookup.
        $isImpersonation = DB::table('user_sessions')
            ->where('token', $token)
            ->where('is_impersonation', true)
            ->exists();

        if (!$isImpersonation) {
            return $next($request);
        }

        $path = ltrim($request->path(), '/');
        if (str_starts_with($path, 'api/')) {
            $path = substr($path, 4);
        }

        // Admin-side routes are driven by admin_token, not auth_token — never block them.
        if ($path === 'admin' || str_starts_with($path, 'admin/')) {
            return $next($request);
        }

        $method = $request->method();

        // PUT/PATCH/DELETE are always writes in this API.
        if (in_array($method, ['PUT', 'PATCH', 'DELETE'], true)) {
            return $this->deny();
        }

        if ($method === 'POST') {
            foreach (self::BLOCKED_POST as $pattern) {
                if (Str::is($pattern, $path)) {
                    return $this->deny();
                }
            }
        }

        return $next($request);
    }

    private function deny()
    {
        return response()->json([
            'status'  => false,
            'message' => 'This action is disabled during admin impersonation (read-only mode).',
            'code'    => 'impersonation_read_only',
        ], 403);
    }
}
