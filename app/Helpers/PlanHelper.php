<?php

namespace App\Helpers;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for firm subscription plan pricing/duration and the
 * renewal expiry calculation. Backed by the admin-managed
 * `firm_subscription_plans` catalog (added 2026-07-11) so nothing is hardcoded.
 *
 * Legacy plan keys that are NOT in the catalog (e.g. the historical 'premium'
 * value written by old manual approvals) still resolve through fallbacks so
 * existing rows keep rendering — no historical record is ever mutated here.
 */
class PlanHelper
{
    /** Legacy/duration fallbacks for keys not present in the catalog. */
    private const LEGACY_MONTHS = [
        'premium'           => 12, // old manual-approval alias for yearly
        'premium-monthly'   => 1,
        'premium-quarterly' => 3,
        'premium-halfyearly' => 6,
        'premium-yearly'    => 12,
    ];

    /** The catalog row for a plan key, or null. */
    public static function find(string $planKey): ?object
    {
        return DB::table('firm_subscription_plans')->where('plan_key', $planKey)->first();
    }

    /** Active plans for the public/firm pricing page, ordered for display. */
    public static function activePlans()
    {
        return DB::table('firm_subscription_plans')
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Duration in months for a plan key. Prefers the catalog; falls back to the
     * legacy map so old rows/keys still compute a sane expiry. Defaults to 1.
     */
    public static function durationMonths(?string $planKey): int
    {
        if (!$planKey) {
            return 1;
        }
        $plan = self::find($planKey);
        if ($plan && (int) $plan->duration_months > 0) {
            return (int) $plan->duration_months;
        }
        return self::LEGACY_MONTHS[$planKey] ?? 1;
    }

    /**
     * Only ACTIVE plans are purchasable. Used to gate initiate/checkout so an
     * admin-deactivated plan can no longer be bought (existing subs keep working).
     */
    public static function isPurchasable(string $planKey): bool
    {
        $plan = self::find($planKey);
        return $plan !== null && (int) $plan->is_active === 1;
    }

    /**
     * Renewal-aware expiry: extend from the firm's current expiry when it is
     * still in the future, otherwise start from now. Uses calendar month math
     * so 15-Oct + 6 months = 15-Apr exactly (never loses remaining validity).
     *
     * @param string|null $currentExpiry existing firm_subscriptions.expires_at
     */
    public static function computeExpiry(string $planKey, ?string $currentExpiry = null): Carbon
    {
        $months = self::durationMonths($planKey);
        $base = now();
        if (!empty($currentExpiry)) {
            $current = Carbon::parse($currentExpiry);
            if ($current->isFuture()) {
                $base = $current;
            }
        }
        return $base->copy()->addMonths($months);
    }

    /**
     * The firm's current active, non-expired premium expiry (for renewal
     * extension), or null when the firm has no live subscription.
     */
    public static function currentActiveExpiry(int $firmId): ?string
    {
        $sub = DB::table('firm_subscriptions')
            ->where('firm_id', $firmId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('expires_at')
            ->first();
        return $sub->expires_at ?? null;
    }

    /**
     * Purchase price for a plan (branch offices pay half, mirroring the existing
     * floor(price/2) rule). Returns the catalog CURRENT price; historical rows
     * keep their own snapshot amount and are never recomputed from here.
     */
    public static function priceFor(string $planKey, bool $isBranch = false): float
    {
        $plan = self::find($planKey);
        $price = $plan ? (float) $plan->price : 0.0;
        return $isBranch ? (float) floor($price / 2) : $price;
    }

    /** Display name + duration label for a plan key (catalog first, then legacy). */
    public static function meta(?string $planKey): array
    {
        if ($planKey) {
            $plan = self::find($planKey);
            if ($plan) {
                return ['name' => $plan->name, 'duration' => $plan->name];
            }
        }
        return match ($planKey) {
            'premium-monthly'   => ['name' => 'Premium Monthly Plan',   'duration' => '1 Month'],
            'premium-quarterly' => ['name' => 'Premium Quarterly Plan', 'duration' => '3 Months'],
            'premium-halfyearly' => ['name' => 'Premium Half-Yearly Plan', 'duration' => '6 Months'],
            'premium-yearly'    => ['name' => 'Premium Yearly Plan',    'duration' => '12 Months'],
            'premium'           => ['name' => 'Premium Yearly Plan',    'duration' => '12 Months'],
            'free', null, ''    => ['name' => 'Free Plan',              'duration' => '—'],
            default             => ['name' => ucwords(str_replace('-', ' ', (string) $planKey)), 'duration' => '—'],
        };
    }
}
