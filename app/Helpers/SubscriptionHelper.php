<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class SubscriptionHelper
{
    public static function isPremiumFirm(int|string $firmId): bool
    {
        if (empty($firmId)) {
            return false;
        }

        // Single source of truth: an ACTIVE, non-expired premium subscription.
        // We intentionally do NOT trust firm_profiles.is_premium — that denormalized
        // flag is never reset on expiry, which let expired-premium firms keep
        // bypassing every paywall. Premium is now always derived dynamically.
        //
        // Any active, non-expired, non-free plan counts as premium. This replaced
        // a hardcoded plan-key whitelist (2026-07-11) so admin-added plan keys
        // (e.g. premium-halfyearly) are recognised automatically — every real plan
        // key is 'premium*'; only 'free'/NULL are excluded.
        return DB::table('firm_subscriptions')
            ->where('firm_id', $firmId)
            ->whereNotNull('plan')
            ->where('plan', '!=', 'free')
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public static function getFirmSubscription(int|string $firmId): ?object
    {
        if (empty($firmId)) {
            return null;
        }
        return DB::table('firm_subscriptions')
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();
    }

    public static function getFirmPlan(int|string $firmId): string
    {
        $subscription = self::getFirmSubscription($firmId);
        if (!$subscription) {
            return 'free';
        }
        if (!empty($subscription->expires_at) && strtotime($subscription->expires_at) < time()) {
            return 'free';
        }
        return $subscription->plan ?? 'free';
    }

    public static function canViewAllApplications(int|string $firmId): bool
    {
        return self::isPremiumFirm($firmId);
    }

    public static function allowedApplicationLimit(int|string $firmId): int
    {
        return self::isPremiumFirm($firmId) ? 999999 : 2;
    }
}
