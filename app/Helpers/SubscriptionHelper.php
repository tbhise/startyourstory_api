<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;

class SubscriptionHelper
{
    /*
    |--------------------------------------------------------------------------
    | Check Premium Subscription
    |--------------------------------------------------------------------------
    */

    public static function isPremiumFirm($firmId): bool
    {
        if (empty($firmId)) {
            return false;
        }

        return DB::table('firm_subscriptions')

            ->where('firm_id', $firmId)

            ->where('plan', 'premium')

            ->where('status', 'active')

            ->where(function ($query) {

                $query

                    ->whereNull('expires_at')

                    ->orWhere(
                        'expires_at',
                        '>',
                        now()
                    );
            })

            ->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Current Subscription
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Get Current Plan
    |--------------------------------------------------------------------------
    */

    public static function getFirmPlan($firmId): string
    {
        $subscription =
            self::getFirmSubscription($firmId);

        if (!$subscription) {
            return 'free';
        }

        /*
        |--------------------------------------------------------------------------
        | Expired
        |--------------------------------------------------------------------------
        */

        if (

            !empty($subscription->expires_at)

            &&

            strtotime($subscription->expires_at)
            < time()

        ) {

            return 'free';
        }

        return $subscription->plan ?? 'free';
    }

    /*
    |--------------------------------------------------------------------------
    | Can View Unlimited Applications
    |--------------------------------------------------------------------------
    */

    public static function canViewAllApplications($firmId): bool
    {
        return self::isPremiumFirm($firmId);
    }

    /*
    |--------------------------------------------------------------------------
    | Allowed Application Visibility Count
    |--------------------------------------------------------------------------
    */

    public static function allowedApplicationLimit(
        $firmId
    ): int {

        if (
            self::isPremiumFirm($firmId)
        ) {

            return 999999;
        }

        return 2;
    }
}
