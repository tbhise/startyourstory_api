<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class SubscriptionHelper
{

    public static function isPremiumFirm($firmId): bool
    {
        if (empty($firmId)) {
            return false;
        }
        return DB::table('firm_subscriptions')
            ->where('firm_id', $firmId)
            ->where('plan', 'like', '%premium%')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })->exists();
    }

    public static function getFirmSubscription($firmId)
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

    public static function getFirmPlan($firmId): string
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

    public static function canViewAllApplications($firmId): bool
    {
        return self::isPremiumFirm($firmId);
    }

    public static function allowedApplicationLimit($firmId): int
    {
        if (self::isPremiumFirm($firmId)) {
            return 999999;
        }
        return 2;
    }
}
