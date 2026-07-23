<?php

namespace App\Services\Payment\Settlement;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Shared settlement for Creator Marketplace escrow payments.
 *
 * Called by BOTH the verify endpoint and the webhook with a gateway-agnostic
 * normalized result. Adds the row lock + in-transaction re-check that the
 * previous per-controller flows were missing, so a verify racing the webhook
 * can never double-activate the engagement or double-notify the creator. The
 * actual payout happens later on deliverable approval — this only HOLDS escrow.
 */
class CreatorEscrowSettlementService
{
    /**
     * @param  object $payment  creator_engagement_payments row (id, engagement_id, amount)
     * @param  array  $result   normalized gateway result
     * @return string 'held' | 'already' | 'failed' | 'pending'
     */
    public function settle(object $payment, array $result): string
    {
        $status = $result['status'] ?? 'failed';

        if ($status === 'pending') {
            return 'pending';
        }

        $isSuccess = $status === 'paid';

        // Amount verification (integer paise) against the accepted bid amount
        // recorded on the payment row.
        if ($isSuccess) {
            $expectedPaise = (int) round(((float) $payment->amount) * 100);
            $actualPaise   = $result['amount'] ?? null;
            if ($actualPaise !== null && $actualPaise !== $expectedPaise) {
                Log::warning('Creator escrow amount mismatch', [
                    'payment_id' => $payment->id, 'expected' => $expectedPaise, 'actual' => $actualPaise,
                ]);
                $isSuccess = false;
            }
        }

        $gatewayPaymentId = $result['gateway_payment_id'] ?? null;
        $raw              = $result['raw'] ?? [];

        return DB::transaction(function () use ($payment, $isSuccess, $gatewayPaymentId, $raw) {
            $fresh = DB::table('creator_engagement_payments')
                ->where('id', $payment->id)
                ->lockForUpdate()
                ->first();

            if ($fresh->status === 'escrow_held') {
                return 'already';
            }

            if (! $isSuccess) {
                // Not paid — keep the attempt retryable (matches prior behaviour).
                DB::table('creator_engagement_payments')
                    ->where('id', $fresh->id)
                    ->update([
                        'status'           => 'pending',
                        'gateway_response' => json_encode($raw),
                        'updated_at'       => now(),
                    ]);
                return 'failed';
            }

            DB::table('creator_engagement_payments')
                ->where('id', $fresh->id)
                ->update([
                    'status'             => 'escrow_held',
                    'gateway_payment_id' => $gatewayPaymentId,
                    'gateway_response'   => json_encode($raw),
                    'updated_at'         => now(),
                ]);

            DB::table('creator_engagements')
                ->where('id', $fresh->engagement_id)
                ->update(['status' => 'active', 'updated_at' => now()]);

            // Notify the creator that funds are in escrow and work can begin.
            $engagement = DB::table('creator_engagements')->where('id', $fresh->engagement_id)->first();
            $project    = DB::table('creator_projects')->where('id', $engagement->creator_requirement_id ?? null)->first();

            DB::table('creator_marketplace_notifications')->insert([
                'user_id'    => $engagement->creator_id,
                'type'       => 'payment_received',
                'title'      => 'Payment received — project is now active!',
                'body'       => 'The firm has paid for "' . ($project->title ?? 'the project') . '". Your project is now active.',
                'data'       => json_encode(['engagement_id' => (int) $fresh->engagement_id]),
                'read_at'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return 'held';
        });
    }
}
