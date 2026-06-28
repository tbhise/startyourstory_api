<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Central recorder for the admin audit trail (admin_activity_logs).
 *
 * Log ONLY important administrative WRITE actions — approvals, rejections,
 * status / permission / money changes, content publish, settings changes, admin
 * management. Do NOT call this for page views, list/read, search, filter,
 * pagination or navigation: those create noise, not an audit trail.
 *
 * Recording is intentionally NON-THROWING — a logging failure is reported to the
 * Laravel log but never breaks the host action (an approval must still succeed
 * even if writing its audit row fails). Place the call AFTER the mutation has
 * succeeded (e.g. after DB::commit) so only real, completed actions are recorded.
 *
 * Usage:
 *   AdminActivityLogger::log($admin, AdminActivityLogger::FIRM_APPROVED,
 *       'firm', $firmId, "Approved firm registration for {$name}.", $request);
 */
class AdminActivityLogger
{
    // ── Action types (extend freely; no schema change required) ────────────────
    // Firm
    public const FIRM_APPROVED            = 'firm_approved';
    public const FIRM_REJECTED            = 'firm_rejected';
    public const FIRM_PREMIUM_CHANGED     = 'firm_premium_changed';
    public const FIRM_DELETED             = 'firm_deleted';
    // Premium / subscription payments
    public const SUBSCRIPTION_APPROVED    = 'subscription_approved';
    public const SUBSCRIPTION_REJECTED    = 'subscription_rejected';
    public const SUBSCRIPTION_CREATED     = 'subscription_created';
    // Wallet / manual payments
    public const WALLET_RECHARGE_APPROVED = 'wallet_recharge_approved';
    public const WALLET_RECHARGE_REJECTED = 'wallet_recharge_rejected';
    // Creator marketplace payments / payouts
    public const CREATOR_PAYMENT_APPROVED = 'creator_payment_approved';
    public const CREATOR_PAYMENT_REJECTED = 'creator_payment_rejected';
    public const CREATOR_PAYOUT_PAID      = 'creator_payout_paid';
    public const CREATOR_PAYOUT_FAILED    = 'creator_payout_failed';
    public const CREATOR_PAYOUTS_FLUSHED  = 'creator_payouts_flushed';
    // Referral rewards
    public const REFERRAL_PAYOUT_APPROVED = 'referral_payout_approved';
    public const REFERRAL_PAYOUT_PAID     = 'referral_payout_paid';
    // Blogs
    public const BLOG_CREATED             = 'blog_created';
    public const BLOG_UPDATED             = 'blog_updated';
    public const BLOG_PUBLISHED           = 'blog_published';
    public const BLOG_UNPUBLISHED         = 'blog_unpublished';
    public const BLOG_DELETED             = 'blog_deleted';
    // Students
    public const STUDENT_DELETED          = 'student_deleted';
    // Moderation (reported student profiles)
    public const REPORT_REVIEWED          = 'report_reviewed';
    public const REPORT_DISMISSED         = 'report_dismissed';
    public const WARNING_ISSUED           = 'warning_issued';
    public const PROFILE_RESTRICTED       = 'profile_restricted';
    public const PROFILE_RESTORED         = 'profile_restored';
    // Campaigns (re-engagement / bulk mail)
    public const CAMPAIGN_EXECUTED        = 'campaign_executed';
    // Settings
    public const PLATFORM_SETTINGS_UPDATED = 'platform_settings_updated';
    public const PAYMENT_SETTINGS_UPDATED  = 'payment_settings_updated';
    // Admin management
    public const ADMIN_CREATED            = 'admin_created';
    public const ADMIN_UPDATED            = 'admin_updated';
    public const ADMIN_ENABLED            = 'admin_enabled';
    public const ADMIN_DISABLED           = 'admin_disabled';
    public const ADMIN_DELETED            = 'admin_deleted';
    // Impersonation (Login as User)
    public const IMPERSONATION_STARTED    = 'impersonation_started';
    public const IMPERSONATION_ENDED      = 'impersonation_ended';

    /**
     * Record one admin action.
     *
     * @param object|null            $admin       The acting admin row (admin_users) — id + name.
     * @param string                 $actionType  One of the constants above.
     * @param string                 $entityType  The kind of thing acted on (firm, blog, …).
     * @param string|int|null        $entityId    The affected entity's id (string-safe for hashids).
     * @param string                 $description Human-readable summary of what happened.
     * @param Request|null           $request     For capturing IP + user agent.
     */
    public static function log(
        ?object $admin,
        string $actionType,
        string $entityType,
        string|int|null $entityId,
        string $description,
        ?Request $request = null
    ): void {
        try {
            DB::table('admin_activity_logs')->insert([
                'admin_id'    => $admin->id ?? null,
                'admin_name'  => $admin->name ?? null,
                'action_type' => $actionType,
                'entity_type' => $entityType,
                'entity_id'   => $entityId !== null ? (string) $entityId : null,
                'description' => mb_substr($description, 0, 2000),
                'ip_address'  => $request?->ip(),
                'user_agent'  => $request ? mb_substr((string) $request->userAgent(), 0, 500) : null,
                'created_at'  => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let audit logging break the host action.
            Log::warning('AdminActivityLogger@log failed: ' . $e->getMessage(), [
                'action_type' => $actionType,
                'entity_type' => $entityType,
                'entity_id'   => $entityId,
            ]);
        }
    }
}
