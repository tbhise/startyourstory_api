<?php

namespace App\Services\Payment\Settlement;

use App\Enums\ActivityType;
use App\Helpers\FirmActivityHelper;
use App\Helpers\PlanHelper;
use App\Helpers\PremiumActivationEmailHelper;
use App\Helpers\ReferralHelper;
use App\Services\ActivityTracker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared settlement for Firm Premium subscriptions.
 *
 * Called by BOTH the verify endpoint and the webhook with a gateway-agnostic
 * normalized result. Adds the row lock + in-transaction re-check that the
 * previous per-controller flows were missing, so a verify racing the webhook can
 * never double-activate premium or fire a duplicate referral payout.
 */
class FirmPremiumSettlementService
{
    /**
     * @param  object $subscription  firm_subscriptions row (id, firm_id, plan, amount, payment_gateway)
     * @param  array  $result        normalized gateway result
     * @return string 'activated' | 'already' | 'failed' | 'pending'
     */
    public function settle(object $subscription, array $result): string
    {
        $status = $result['status'] ?? 'failed';

        if ($status === 'pending') {
            return 'pending';
        }

        $isSuccess = $status === 'paid';

        // Amount verification (integer paise) against the price snapshotted on the
        // subscription row at purchase time.
        if ($isSuccess) {
            $expectedPaise = (int) round(((float) $subscription->amount) * 100);
            $actualPaise   = $result['amount'] ?? null;
            if ($actualPaise !== null && $actualPaise !== $expectedPaise) {
                Log::warning('Firm premium amount mismatch', [
                    'subscription_id' => $subscription->id, 'expected' => $expectedPaise, 'actual' => $actualPaise,
                ]);
                $isSuccess = false;
            }
        }

        $gatewayPaymentId = $result['gateway_payment_id'] ?? null;
        $raw              = $result['raw'] ?? [];

        $outcome = DB::transaction(function () use ($subscription, $isSuccess, $gatewayPaymentId, $raw) {
            $fresh = DB::table('firm_subscriptions')
                ->where('id', $subscription->id)
                ->lockForUpdate()
                ->first();

            if ($fresh->payment_status === 'paid') {
                return 'already';
            }

            if (! $isSuccess) {
                DB::table('firm_subscriptions')
                    ->where('id', $fresh->id)
                    ->update([
                        'payment_status'    => 'failed',
                        'razorpay_response' => json_encode($raw),
                        'updated_at'        => now(),
                    ]);
                return 'failed';
            }

            // Renewal-aware expiry: extend from the firm's current active expiry
            // when it is still in the future, else from now. Excludes THIS pending
            // row (not yet active) so it never counts as its own base.
            $currentExpiry = PlanHelper::currentActiveExpiry((int) $fresh->firm_id);
            $expiresAt     = PlanHelper::computeExpiry($fresh->plan, $currentExpiry);

            DB::table('firm_subscriptions')
                ->where('id', $fresh->id)
                ->update([
                    'gateway_payment_id' => $gatewayPaymentId,
                    'razorpay_response'  => json_encode($raw),
                    'payment_status'     => 'paid',
                    'status'             => 'active',
                    'payment_date'       => now(),
                    'starts_at'          => now(),
                    'expires_at'         => $expiresAt,
                    'updated_at'         => now(),
                ]);

            // Supersede any OTHER active rows (their validity is folded in above).
            DB::table('firm_subscriptions')
                ->where('firm_id', $fresh->firm_id)
                ->where('id', '!=', $fresh->id)
                ->where('status', 'active')
                ->update(['status' => 'expired', 'updated_at' => now()]);

            DB::table('firm_profiles')
                ->where('id', $fresh->firm_id)
                ->update(['is_premium' => 1, 'updated_at' => now()]);

            // Firm referral: create a pending payout if this firm was referred
            // (helper is idempotent via a one-payout-per-firm existence check).
            ReferralHelper::onFirmPremiumActivated((int) $fresh->firm_id);

            DB::table('payment_logs')->insert([
                'firm_subscription_id' => $fresh->id,
                'event_type'           => ($fresh->payment_gateway ?: 'phonepe') . '_payment_verified',
                'payload'              => json_encode(['merchant_txn_id' => $fresh->gateway_order_id, 'gateway_payment_id' => $gatewayPaymentId]),
                'created_at'           => now(),
            ]);

            return 'activated';
        });

        // Post-commit side effects (non-blocking; never affect activation).
        if ($outcome === 'activated') {
            $firmUserId = DB::table('firm_profiles')->where('id', $subscription->firm_id)->value('user_id');

            ActivityTracker::log(ActivityTracker::FIRM, $firmUserId, ActivityType::SUBSCRIPTION_PURCHASED, [
                'subscription_id' => (int) $subscription->id,
                'plan'            => $subscription->plan,
                'amount'          => (float) $subscription->amount,
            ]);
            FirmActivityHelper::log($subscription->firm_id, FirmActivityHelper::PREMIUM_PURCHASED, 'Purchased Premium (' . $subscription->plan . ')');
            // Exactly-once vs the other path (marker row in payment_logs). Activation
            // type = gateway name; 'phonepe' preserves the existing email wording.
            PremiumActivationEmailHelper::send((int) $subscription->id, (string) ($subscription->payment_gateway ?: 'phonepe'));
        }

        return $outcome;
    }
}
