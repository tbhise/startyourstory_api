<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Centralized, cached access to dynamic business settings (system_settings).
 *
 * Every business value (referral rewards, welcome bonus, fees, limits, future
 * rules) should be read through this service instead of being hardcoded:
 *
 *   SystemSettingService::getStudentReferralReward();
 *   SystemSettingService::get('some_future_key', $default);
 *
 * Values are cached per key (rememberForever) and the cache is busted whenever a
 * setting is updated, so reads are cheap and always current.
 */
class SystemSettingService
{
    private const CACHE_PREFIX = 'system_setting:';

    // Default fallbacks — used when a row is missing so behaviour never breaks.
    public const DEFAULTS = [
        'student_referral_reward'      => 50,
        'firm_premium_purchase_reward' => 2000,
        'welcome_bonus_coins'          => 100,
        'free_applications_count'      => 3,
        'application_fee_amount'       => 49,
        'minimum_wallet_recharge'      => 150,
    ];

    /**
     * Fetch a setting, cast to its declared type. Returns $default (or the known
     * DEFAULTS fallback) when the key is absent.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $fallback = $default ?? (self::DEFAULTS[$key] ?? null);

        try {
            $row = Cache::rememberForever(self::CACHE_PREFIX . $key, function () use ($key) {
                $r = SystemSetting::where('setting_key', $key)->first(['setting_value', 'setting_type']);
                // Cache a plain array (or null) — never an Eloquent model.
                return $r ? ['value' => $r->setting_value, 'type' => $r->setting_type] : null;
            });

            if ($row === null) {
                return $fallback;
            }

            return self::castValue($row['value'], $row['type']);
        } catch (\Throwable $e) {
            Log::error("SystemSettingService@get($key): " . $e->getMessage());
            return $fallback;
        }
    }

    /**
     * Update a setting's value, write an audit row, and bust the cache.
     * Returns the new (cast) value.
     */
    public static function set(string $key, mixed $value, ?object $admin = null): mixed
    {
        $setting = SystemSetting::where('setting_key', $key)->first();
        if (!$setting) {
            throw new \InvalidArgumentException("Unknown setting: {$key}");
        }

        $oldValue = $setting->setting_value;
        $newValue = (string) $value;

        DB::transaction(function () use ($setting, $key, $oldValue, $newValue, $admin) {
            $setting->update(['setting_value' => $newValue]);

            DB::table('system_setting_audits')->insert([
                'setting_key'   => $key,
                'old_value'     => $oldValue,
                'new_value'     => $newValue,
                'admin_user_id' => $admin->id ?? null,
                'admin_name'    => $admin->name ?? null,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        });

        self::forgetCache($key);

        return self::castValue($newValue, $setting->setting_type);
    }

    public static function forgetCache(string $key): void
    {
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) return null;

        return match ($type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true),
            default   => $value,
        };
    }

    // ── Typed convenience getters (spec) ──────────────────────────────────────

    public static function getStudentReferralReward(): int
    {
        return (int) self::get('student_referral_reward', self::DEFAULTS['student_referral_reward']);
    }

    public static function getFirmPremiumPurchaseReward(): float
    {
        return (float) self::get('firm_premium_purchase_reward', self::DEFAULTS['firm_premium_purchase_reward']);
    }

    public static function getWelcomeBonusCoins(): int
    {
        return (int) self::get('welcome_bonus_coins', self::DEFAULTS['welcome_bonus_coins']);
    }

    public static function getApplicationFeeAmount(): float
    {
        return (float) self::get('application_fee_amount', self::DEFAULTS['application_fee_amount']);
    }

    public static function getFreeApplicationsCount(): int
    {
        return (int) self::get('free_applications_count', self::DEFAULTS['free_applications_count']);
    }

    public static function getMinimumWalletRecharge(): float
    {
        return (float) self::get('minimum_wallet_recharge', self::DEFAULTS['minimum_wallet_recharge']);
    }
}
