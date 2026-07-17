<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\InsufficientFundsException;
use App\Services\SystemSettingService;

/**
 * SYS Coins — a points currency kept STRICTLY separate from wallet money.
 *
 * Mirrors WalletHelper's available/hold/consumed + ledger + holds pattern so the
 * application-payment lifecycle (hold on apply → consume on interview accepted →
 * release on reject/expiry) behaves identically to the wallet. Coins are integers.
 *
 * Tables: sys_coin_accounts, sys_coin_transactions, sys_coin_holds.
 */
class SysCoinHelper
{
    /** Fallback only — canonical value lives in system_settings (welcome_bonus_coins). */
    const WELCOME_BONUS          = 100;
    /** Fallback only — canonical value lives in system_settings (student_referral_reward). */
    const STUDENT_REFERRAL_BONUS = 50;
    /** Coins required to pay for one job application. */
    const APPLICATION_COST       = 50;
    /** Days a coin hold survives with no recruiter action before auto-release. */
    const HOLD_DAYS              = 10;

    /** Ledger transaction types. */
    const TYPE_WELCOME_BONUS        = 'WELCOME_BONUS';
    const TYPE_REFERRAL_BONUS       = 'REFERRAL_BONUS';
    const TYPE_APPLICATION_HOLD     = 'APPLICATION_HOLD';
    const TYPE_APPLICATION_RELEASE  = 'APPLICATION_RELEASE';
    const TYPE_APPLICATION_CONSUMED = 'APPLICATION_CONSUMED';
    const TYPE_ADMIN_CREDIT         = 'ADMIN_CREDIT';
    const TYPE_BLOG_REWARD          = 'BLOG_REWARD';

    /*
    |--------------------------------------------------------------------------
    | Get or create a coin account for a user
    |--------------------------------------------------------------------------
    */
    public static function getOrCreate(int $userId): object
    {
        $account = DB::table('sys_coin_accounts')->where('user_id', $userId)->first();
        if (!$account) {
            DB::table('sys_coin_accounts')->insert([
                'user_id'         => $userId,
                'available_coins' => 0,
                'hold_coins'      => 0,
                'consumed_coins'  => 0,
                'lifetime_earned' => 0,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
            $account = DB::table('sys_coin_accounts')->where('user_id', $userId)->first();
        }
        return $account;
    }

    public static function getBalance(int $userId): int
    {
        return (int) self::getOrCreate($userId)->available_coins;
    }

    public static function hasEnoughCoins(int $userId, int $amount = self::APPLICATION_COST): bool
    {
        return self::getBalance($userId) >= $amount;
    }

    /*
    |--------------------------------------------------------------------------
    | Credit coins (welcome bonus, referral bonus, admin credit, blog reward…)
    |--------------------------------------------------------------------------
    */
    public static function grant(
        int $userId,
        int $amount,
        string $type,
        ?string $referenceType = null,
        ?int $referenceId = null,
        string $description = ''
    ): void {
        if ($amount <= 0) return;

        DB::transaction(function () use ($userId, $amount, $type, $referenceType, $referenceId, $description) {
            $acc    = self::getOrCreate($userId);
            $before = (int) $acc->available_coins;
            $after  = $before + $amount;

            DB::table('sys_coin_accounts')
                ->where('user_id', $userId)
                ->update([
                    'available_coins' => $after,
                    'lifetime_earned' => DB::raw('lifetime_earned + ' . $amount),
                    'updated_at'      => now(),
                ]);

            DB::table('sys_coin_transactions')->insert([
                'user_id'        => $userId,
                'amount'         => $amount,
                'type'           => $type,
                'reference_type' => $referenceType,
                'reference_id'   => $referenceId,
                'description'    => $description ?: "{$amount} SYS Coins credited",
                'balance_before' => $before,
                'balance_after'  => $after,
                'created_at'     => now(),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Hold — move coins from available to hold when student applies
    |--------------------------------------------------------------------------
    */
    public static function hold(int $userId, int $applicationId, int $jobId): int
    {
        $holdId = 0;

        DB::transaction(function () use ($userId, $applicationId, $jobId, &$holdId) {
            self::getOrCreate($userId);

            // Lock the coin account row for the lifetime of the (outer) transaction
            // so concurrent applies are serialized — prevents double-spend.
            $acc    = DB::table('sys_coin_accounts')->where('user_id', $userId)->lockForUpdate()->first();
            $amount = self::APPLICATION_COST;
            $before = (int) $acc->available_coins;

            // Re-validate INSIDE the locked transaction — a concurrent request that
            // already spent the coins fails here instead of going negative.
            if ($before < $amount) {
                throw new InsufficientFundsException('Insufficient SYS Coins');
            }

            $after = $before - $amount;

            DB::table('sys_coin_accounts')
                ->where('user_id', $userId)
                ->update([
                    'available_coins' => $after,
                    'hold_coins'      => DB::raw('hold_coins + ' . $amount),
                    'updated_at'      => now(),
                ]);

            $holdId = DB::table('sys_coin_holds')->insertGetId([
                'user_id'        => $userId,
                'application_id' => $applicationId,
                'job_id'         => $jobId,
                'amount'         => $amount,
                'status'         => 'held',
                'held_at'        => now(),
                'expires_at'     => now()->addDays(self::HOLD_DAYS),
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $txId = DB::table('sys_coin_transactions')->insertGetId([
                'user_id'        => $userId,
                'amount'         => $amount,
                'type'           => self::TYPE_APPLICATION_HOLD,
                'reference_type' => 'application',
                'reference_id'   => $holdId,
                'application_id' => $applicationId,
                'job_id'         => $jobId,
                'description'    => "{$amount} SYS Coins held for application",
                'balance_before' => $before,
                'balance_after'  => $after,
                'created_at'     => now(),
            ]);

            DB::table('sys_coin_holds')
                ->where('id', $holdId)
                ->update(['hold_transaction_id' => $txId]);
        });

        return $holdId;
    }

    /*
    |--------------------------------------------------------------------------
    | Consume — move hold → consumed when interview is accepted
    | (no-op when the application was not paid with coins)
    |--------------------------------------------------------------------------
    */
    public static function consume(int $applicationId): void
    {
        DB::transaction(function () use ($applicationId) {
            // Lock the hold row + re-check status under the lock so two concurrent
            // settlements cannot both move the same hold → double consume.
            $hold = DB::table('sys_coin_holds')
                ->where('application_id', $applicationId)
                ->where('status', 'held')
                ->lockForUpdate()
                ->first();

            if (!$hold) return; // free / wallet application, or already settled

            $acc = DB::table('sys_coin_accounts')->where('user_id', $hold->user_id)->lockForUpdate()->first();

            DB::table('sys_coin_accounts')
                ->where('user_id', $hold->user_id)
                ->update([
                    'hold_coins'     => DB::raw('hold_coins - ' . $hold->amount),
                    'consumed_coins' => DB::raw('consumed_coins + ' . $hold->amount),
                    'updated_at'     => now(),
                ]);

            $txId = DB::table('sys_coin_transactions')->insertGetId([
                'user_id'        => $hold->user_id,
                'amount'         => $hold->amount,
                'type'           => self::TYPE_APPLICATION_CONSUMED,
                'reference_type' => 'application',
                'reference_id'   => $hold->id,
                'application_id' => $applicationId,
                'job_id'         => $hold->job_id,
                'description'    => "SYS Coins consumed — interview accepted",
                'balance_before' => (int) $acc->available_coins,
                'balance_after'  => (int) $acc->available_coins,
                'created_at'     => now(),
            ]);

            DB::table('sys_coin_holds')
                ->where('id', $hold->id)
                ->update([
                    'status'                => 'consumed',
                    'consumed_at'           => now(),
                    'settle_transaction_id' => $txId,
                    'updated_at'            => now(),
                ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Release — return hold to available (rejection or auto-expiry)
    | (no-op when the application was not paid with coins)
    |--------------------------------------------------------------------------
    */
    public static function release(int $applicationId, string $reason = 'rejected'): void
    {
        DB::transaction(function () use ($applicationId, $reason) {
            // Lock the hold row + re-check status under the lock so a reject racing
            // the auto-expiry job cannot release the same hold twice.
            $hold = DB::table('sys_coin_holds')
                ->where('application_id', $applicationId)
                ->where('status', 'held')
                ->lockForUpdate()
                ->first();

            if (!$hold) return; // free / wallet application, or already settled

            $acc    = DB::table('sys_coin_accounts')->where('user_id', $hold->user_id)->lockForUpdate()->first();
            $before = (int) $acc->available_coins;
            $after  = $before + (int) $hold->amount;

            DB::table('sys_coin_accounts')
                ->where('user_id', $hold->user_id)
                ->update([
                    'available_coins' => $after,
                    'hold_coins'      => DB::raw('hold_coins - ' . $hold->amount),
                    'updated_at'      => now(),
                ]);

            $description = match ($reason) {
                'rejected'     => "{$hold->amount} SYS Coins returned — application rejected",
                'auto_expired' => "{$hold->amount} SYS Coins returned — no recruiter action in " . self::HOLD_DAYS . " days",
                default        => "SYS Coin hold released",
            };

            $txId = DB::table('sys_coin_transactions')->insertGetId([
                'user_id'        => $hold->user_id,
                'amount'         => $hold->amount,
                'type'           => self::TYPE_APPLICATION_RELEASE,
                'reference_type' => 'application',
                'reference_id'   => $hold->id,
                'application_id' => $applicationId,
                'job_id'         => $hold->job_id,
                'description'    => $description,
                'balance_before' => $before,
                'balance_after'  => $after,
                'created_at'     => now(),
            ]);

            $holdStatus = $reason === 'auto_expired' ? 'expired' : 'released';

            DB::table('sys_coin_holds')
                ->where('id', $hold->id)
                ->update([
                    'status'                => $holdStatus,
                    'released_at'           => now(),
                    'release_reason'        => $reason,
                    'settle_transaction_id' => $txId,
                    'updated_at'            => now(),
                ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Welcome bonus — 100 coins for PROVISIONAL students, once.
    | Trigger: email verified AND profile completed. Idempotent & order-independent.
    |--------------------------------------------------------------------------
    */
    public static function maybeGrantWelcomeBonus(int $userId): void
    {
        try {
            $user = DB::table('users')->where('id', $userId)->first();
            if (!$user || $user->role !== 'student') return;
            if (empty($user->email_verified_at) || (int) ($user->profile_completed ?? 0) !== 1) return;

            $profile = DB::table('student_profiles')
                ->where('user_id', $userId)
                ->select('registration_type', 'looking_for')
                ->first();

            if (!$profile) return;

            // "Already Doing Articleship" students are not job-seeking and are
            // excluded from onboarding rewards — skip the welcome bonus entirely.
            // This guard is authoritative regardless of registration_type (they
            // derive to "confirm", but must stay excluded even on stale rows).
            if (strtolower(trim((string) ($profile->looking_for ?? ''))) === 'already_doing_articleship') return;

            // Only provisional-registration students qualify.
            if (strtolower(trim((string) ($profile->registration_type ?? ''))) !== RegistrationTypeHelper::PROVISIONAL) return;

            // Already granted?
            $exists = DB::table('sys_coin_transactions')
                ->where('user_id', $userId)
                ->where('type', self::TYPE_WELCOME_BONUS)
                ->exists();
            if ($exists) return;

            // Amount comes from dynamic Platform Settings (falls back to WELCOME_BONUS).
            $amount = SystemSettingService::getWelcomeBonusCoins();
            self::grant(
                $userId,
                $amount,
                self::TYPE_WELCOME_BONUS,
                'welcome',
                $userId,
                $amount . " SYS Coins welcome bonus"
            );
        } catch (\Exception $e) {
            Log::error('SysCoinHelper@maybeGrantWelcomeBonus: ' . $e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Student referral bonus — 10 coins to the REFERRER, once per referred student.
    | Trigger: referred student email verified AND profile completed.
    |--------------------------------------------------------------------------
    */
    public static function maybeGrantStudentReferralBonus(int $referredUserId): void
    {
        try {
            $referred = DB::table('users')->where('id', $referredUserId)->first();
            if (!$referred || $referred->role !== 'student') return;
            if (empty($referred->email_verified_at) || (int) ($referred->profile_completed ?? 0) !== 1) return;
            if (empty($referred->referred_by)) return;

            $referrer = DB::table('users')
                ->where('id', $referred->referred_by)
                ->where('is_deleted', false)
                ->first();
            if (!$referrer) return;

            // Already rewarded for this referred student?
            $exists = DB::table('sys_coin_transactions')
                ->where('type', self::TYPE_REFERRAL_BONUS)
                ->where('reference_type', 'referral')
                ->where('reference_id', $referredUserId)
                ->exists();
            if ($exists) return;

            // Amount comes from dynamic Platform Settings (falls back to STUDENT_REFERRAL_BONUS).
            $amount = SystemSettingService::getStudentReferralReward();
            self::grant(
                (int) $referrer->id,
                $amount,
                self::TYPE_REFERRAL_BONUS,
                'referral',
                $referredUserId,
                $amount . " SYS Coins for referring " . ($referred->name ?? 'a student')
            );
        } catch (\Exception $e) {
            Log::error('SysCoinHelper@maybeGrantStudentReferralBonus: ' . $e->getMessage());
        }
    }
}
