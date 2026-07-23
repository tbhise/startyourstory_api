<?php

namespace App\Services\Payment\Settlement;

use App\Enums\ActivityType;
use App\Helpers\WalletHelper;
use App\Services\ActivityTracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared settlement for Student Wallet recharges.
 *
 * Called by BOTH the verify endpoint and the webhook with a gateway-agnostic
 * normalized result (see PaymentGateway). Row-locked + re-checked under lock so
 * a verify racing the webhook (or a replayed webhook) can never double-credit.
 */
class WalletSettlementService
{
    /**
     * @param  object $recharge  wallet_recharges row (needs id, user_id, amount)
     * @param  array  $result    normalized gateway result
     * @return string 'credited' | 'already' | 'failed' | 'pending'
     */
    public function settle(object $recharge, array $result): string
    {
        $status = $result['status'] ?? 'failed';

        // Leave genuinely pending payments untouched — a later webhook finalizes.
        if ($status === 'pending') {
            return 'pending';
        }

        $isSuccess = $status === 'paid';

        // Amount verification (integer paise): the gateway-confirmed amount must
        // equal the amount we recorded for this recharge.
        if ($isSuccess) {
            $expectedPaise = (int) round(((float) $recharge->amount) * 100);
            $actualPaise   = $result['amount'] ?? null;
            if ($actualPaise !== null && $actualPaise !== $expectedPaise) {
                Log::warning('Wallet recharge amount mismatch', [
                    'recharge_id' => $recharge->id, 'expected' => $expectedPaise, 'actual' => $actualPaise,
                ]);
                $isSuccess = false;
            }
        }

        $gatewayPaymentId = $result['gateway_payment_id'] ?? null;
        $raw              = $result['raw'] ?? [];

        $outcome = DB::transaction(function () use ($recharge, $isSuccess, $gatewayPaymentId, $raw) {
            $fresh = DB::table('wallet_recharges')
                ->where('id', $recharge->id)
                ->lockForUpdate()
                ->first();

            if ($fresh->payment_status === 'paid') {
                return 'already';
            }

            if ($isSuccess) {
                DB::table('wallet_recharges')
                    ->where('id', $fresh->id)
                    ->update([
                        'gateway_payment_id' => $gatewayPaymentId,
                        'payment_status'     => 'paid',
                        'status'             => 'approved',
                        'gateway_response'   => json_encode($raw),
                        'approved_at'        => now(),
                        'updated_at'         => now(),
                    ]);

                WalletHelper::credit($fresh->user_id, (float) $fresh->amount, $fresh->id);
                return 'credited';
            }

            DB::table('wallet_recharges')
                ->where('id', $fresh->id)
                ->update([
                    'payment_status'   => 'failed',
                    'status'           => 'rejected',
                    'gateway_response' => json_encode($raw),
                    'rejected_at'      => now(),
                    'updated_at'       => now(),
                ]);
            return 'failed';
        });

        // Activity log (async, non-blocking) — only a fresh credit is tracked.
        if ($outcome === 'credited') {
            ActivityTracker::log(ActivityTracker::STUDENT, $recharge->user_id, ActivityType::WALLET_RECHARGED, [
                'recharge_id' => (int) $recharge->id,
                'amount'      => (float) $recharge->amount,
            ]);
        }

        return $outcome;
    }
}
