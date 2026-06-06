<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletHelper
{
    const APPLICATION_FEE = 49.00;
    const FREE_LIMIT      = 3;     // fallback — real default lives in platform_settings
    const HOLD_DAYS       = 10;

    public static function getDefaultFreeLimit(): int
    {
        $v = DB::table('platform_settings')
            ->where('key', 'free_applications_limit')
            ->value('value');
        return (int) ($v ?? self::FREE_LIMIT);
    }

    /*
    |--------------------------------------------------------------------------
    | Get or create wallet for a student
    |--------------------------------------------------------------------------
    */
    public static function getOrCreate(int $userId): object
    {
        $wallet = DB::table('student_wallets')->where('user_id', $userId)->first();
        if (!$wallet) {
            DB::table('student_wallets')->insertGetId([
                'user_id'                 => $userId,
                'available_balance'       => 0.00,
                'hold_balance'            => 0.00,
                'consumed_balance'        => 0.00,
                'free_applications_used'  => 0,
                'free_applications_limit' => self::getDefaultFreeLimit(),
                'created_at'              => now(),
                'updated_at'              => now(),
            ]);
            $wallet = DB::table('student_wallets')->where('user_id', $userId)->first();
        }
        return $wallet;
    }

    /*
    |--------------------------------------------------------------------------
    | Quota helpers
    |--------------------------------------------------------------------------
    */
    public static function isFreeApplication(int $userId): bool
    {
        $w = self::getOrCreate($userId);
        return $w->free_applications_used < $w->free_applications_limit;
    }

    public static function hasEnoughBalance(int $userId): bool
    {
        $w = self::getOrCreate($userId);
        return (float) $w->available_balance >= self::APPLICATION_FEE;
    }

    public static function incrementFreeUsage(int $userId): void
    {
        DB::table('student_wallets')
            ->where('user_id', $userId)
            ->increment('free_applications_used');
        DB::table('student_wallets')->where('user_id', $userId)->update(['updated_at' => now()]);
    }

    /*
    |--------------------------------------------------------------------------
    | Credit — called after recharge approved (online or manual)
    |--------------------------------------------------------------------------
    */
    public static function credit(int $userId, float $amount, int $rechargeId, string $description = ''): void
    {
        DB::transaction(function () use ($userId, $amount, $rechargeId, $description) {
            $w = self::getOrCreate($userId);
            $before = (float) $w->available_balance;
            $after  = $before + $amount;

            DB::table('student_wallets')
                ->where('user_id', $userId)
                ->update(['available_balance' => $after, 'updated_at' => now()]);

            DB::table('wallet_transactions')->insert([
                'user_id'        => $userId,
                'amount'         => $amount,
                'type'           => 'credit',
                'reference_type' => 'recharge',
                'reference_id'   => $rechargeId,
                'description'    => $description ?: "Wallet recharged ₹{$amount}",
                'balance_before' => $before,
                'balance_after'  => $after,
                'created_at'     => now(),
            ]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Hold — move amount from available to hold when student applies
    |--------------------------------------------------------------------------
    */
    public static function hold(int $userId, int $applicationId, int $jobId): int
    {
        $holdId = 0;

        DB::transaction(function () use ($userId, $applicationId, $jobId, &$holdId) {
            $w      = self::getOrCreate($userId);
            $amount = self::APPLICATION_FEE;
            $before = (float) $w->available_balance;
            $after  = $before - $amount;

            DB::table('student_wallets')
                ->where('user_id', $userId)
                ->update([
                    'available_balance' => $after,
                    'hold_balance'      => DB::raw('hold_balance + ' . $amount),
                    'updated_at'        => now(),
                ]);

            $holdId = DB::table('application_holds')->insertGetId([
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

            $txId = DB::table('wallet_transactions')->insertGetId([
                'user_id'        => $userId,
                'amount'         => $amount,
                'type'           => 'hold',
                'reference_type' => 'application',
                'reference_id'   => $holdId,
                'application_id' => $applicationId,
                'job_id'         => $jobId,
                'description'    => "₹{$amount} held for application",
                'balance_before' => $before,
                'balance_after'  => $after,
                'created_at'     => now(),
            ]);

            DB::table('application_holds')
                ->where('id', $holdId)
                ->update(['hold_transaction_id' => $txId]);
        });

        return $holdId;
    }

    /*
    |--------------------------------------------------------------------------
    | Consume — move hold → consumed when interview is accepted
    |--------------------------------------------------------------------------
    */
    public static function consume(int $applicationId): void
    {
        DB::transaction(function () use ($applicationId) {
            $hold = DB::table('application_holds')
                ->where('application_id', $applicationId)
                ->where('status', 'held')
                ->first();

            if (!$hold) return; // free application or already settled

            $w = self::getOrCreate($hold->user_id);

            DB::table('student_wallets')
                ->where('user_id', $hold->user_id)
                ->update([
                    'hold_balance'      => DB::raw('hold_balance - ' . $hold->amount),
                    'consumed_balance'  => DB::raw('consumed_balance + ' . $hold->amount),
                    'updated_at'        => now(),
                ]);

            $txId = DB::table('wallet_transactions')->insertGetId([
                'user_id'        => $hold->user_id,
                'amount'         => $hold->amount,
                'type'           => 'consume',
                'reference_type' => 'application',
                'reference_id'   => $hold->id,
                'application_id' => $applicationId,
                'job_id'         => $hold->job_id,
                'description'    => "Application fee consumed — interview accepted",
                'balance_before' => (float) $w->available_balance,
                'balance_after'  => (float) $w->available_balance,
                'created_at'     => now(),
            ]);

            DB::table('application_holds')
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
    |--------------------------------------------------------------------------
    */
    public static function release(int $applicationId, string $reason = 'rejected'): void
    {
        DB::transaction(function () use ($applicationId, $reason) {
            $hold = DB::table('application_holds')
                ->where('application_id', $applicationId)
                ->where('status', 'held')
                ->first();

            if (!$hold) return; // free application or already settled

            $w      = self::getOrCreate($hold->user_id);
            $before = (float) $w->available_balance;
            $after  = $before + (float) $hold->amount;

            DB::table('student_wallets')
                ->where('user_id', $hold->user_id)
                ->update([
                    'available_balance' => $after,
                    'hold_balance'      => DB::raw('hold_balance - ' . $hold->amount),
                    'updated_at'        => now(),
                ]);

            $description = match ($reason) {
                'rejected'     => "₹{$hold->amount} returned — application rejected",
                'auto_expired' => "₹{$hold->amount} returned — no recruiter action in " . self::HOLD_DAYS . " days",
                default        => "Hold released",
            };

            $txId = DB::table('wallet_transactions')->insertGetId([
                'user_id'        => $hold->user_id,
                'amount'         => $hold->amount,
                'type'           => 'release',
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

            DB::table('application_holds')
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
}
