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

        // Fast path: synced flag on firm_profiles (set by payment/admin flows)
        if ((bool) DB::table('firm_profiles')->where('id', $firmId)->value('is_premium')) {
            return true;
        }

        // Fallback: check firm_subscriptions directly for firms not yet backfilled
        return DB::table('firm_subscriptions')
            ->where('firm_id', $firmId)
            ->whereIn('plan', ['premium', 'premium-monthly', 'premium-quarterly', 'premium-yearly'])
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
