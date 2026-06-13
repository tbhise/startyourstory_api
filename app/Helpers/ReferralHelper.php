<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Referral rewards built on the existing referral linkage
 * (users.referral_code / referred_by / referral_count).
 *
 * - Student referrals are rewarded in SYS Coins (see SysCoinHelper).
 * - Firm referrals create a real-money PAYOUT RECORD when the referred firm buys
 *   premium. Money is settled externally by an admin (mark-only) — never auto-credited.
 */
class ReferralHelper
{
    /** Real-money reward (₹) to the referrer when a referred firm buys premium. */
    const FIRM_REFERRAL_REWARD = 2000.00;

    /*
    |--------------------------------------------------------------------------
    | Validate a referral code (also used for live registration-form feedback)
    |--------------------------------------------------------------------------
    | Returns: valid, self, referrer_name, referred_role.
    | "self" is true when the code belongs to an account with the same email or
    | mobile as the person registering (self-referral).
    */
    public static function validateCode(?string $code, ?string $email = null, ?string $mobile = null): array
    {
        $result = ['valid' => false, 'self' => false, 'referrer_name' => null, 'referrer_role' => null];

        $code = strtoupper(trim((string) $code));
        if ($code === '') return $result;

        $owner = DB::table('users')
            ->whereRaw('UPPER(referral_code) = ?', [$code])
            ->where('is_deleted', false)
            ->first();

        if (!$owner) return $result;

        $result['valid']         = true;
        $result['referrer_name'] = $owner->name;
        $result['referrer_role'] = $owner->role;

        $email  = trim((string) $email);
        $mobile = trim((string) $mobile);
        if (($email !== '' && strcasecmp($owner->email, $email) === 0)
            || ($mobile !== '' && (string) $owner->mobile === $mobile)) {
            $result['self'] = true;
        }

        return $result;
    }

    /**
     * Resolve a referral code to the referrer's user id, dropping self-referrals.
     * Used by the registration controllers: returns the referrer's user id, or null
     * when the code is empty, invalid, or a self-referral (registration still proceeds).
     */
    public static function resolveReferrerId(?string $code, ?string $email = null, ?string $mobile = null): ?int
    {
        $code = strtoupper(trim((string) $code));
        if ($code === '') return null;

        $owner = DB::table('users')
            ->whereRaw('UPPER(referral_code) = ?', [$code])
            ->where('is_deleted', false)
            ->first();
        if (!$owner) return null;

        // Self-referral (same email or mobile) → ignore the code, registration continues.
        $email  = trim((string) $email);
        $mobile = trim((string) $mobile);
        if (($email !== '' && strcasecmp($owner->email, $email) === 0)
            || ($mobile !== '' && (string) $owner->mobile === $mobile)) {
            return null;
        }

        return (int) $owner->id;
    }

    /*
    |--------------------------------------------------------------------------
    | Firm premium activated → create a pending ₹2,000 payout for the referrer.
    | $firmProfileId is a firm_profiles.id (every activation site flips is_premium
    | via `firm_profiles WHERE id = X`, so the same X resolves the firm's user_id).
    | Idempotent: one payout per referred firm (also enforced by a UNIQUE index).
    |--------------------------------------------------------------------------
    */
    public static function onFirmPremiumActivated(int $firmProfileId): void
    {
        try {
            $profile = DB::table('firm_profiles')->where('id', $firmProfileId)->first();
            if (!$profile) return;

            $firmUserId = (int) $profile->user_id;
            $firmUser   = DB::table('users')->where('id', $firmUserId)->first();
            if (!$firmUser || empty($firmUser->referred_by)) return;

            $referrer = DB::table('users')
                ->where('id', $firmUser->referred_by)
                ->where('is_deleted', false)
                ->first();
            if (!$referrer) return;

            // One payout per referred firm.
            $exists = DB::table('referral_payouts')
                ->where('referred_user_id', $firmUserId)
                ->exists();
            if ($exists) return;

            // Best-effort metadata: the firm's most recent subscription (firm_id is
            // stored as either profile id or user id across the codebase — cover both).
            $sub = DB::table('firm_subscriptions')
                ->whereIn('firm_id', [$firmProfileId, $firmUserId])
                ->orderByDesc('id')
                ->first();

            DB::table('referral_payouts')->insert([
                'referrer_user_id'     => (int) $referrer->id,
                'referred_user_id'     => $firmUserId,
                'firm_subscription_id' => $sub->id ?? null,
                'plan'                 => $sub->plan ?? null,
                'reward_amount'        => self::FIRM_REFERRAL_REWARD,
                'status'               => 'pending',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // Unique-constraint race — payout already exists; safe to ignore.
            if (($e->errorInfo[1] ?? null) !== 1062) {
                Log::error('ReferralHelper@onFirmPremiumActivated: ' . $e->getMessage());
            }
        } catch (\Exception $e) {
            Log::error('ReferralHelper@onFirmPremiumActivated: ' . $e->getMessage());
        }
    }
}
