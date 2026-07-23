<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\Settlement\CreatorEscrowSettlementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PhonePeEngagementController extends Controller
{
    public function __construct(
        private PaymentManager $payments,
        private CreatorEscrowSettlementService $settlement,
    ) {}

    private function getFirmProfile(int $userId): ?object
    {
        return DB::table('firm_profiles')->where('user_id', $userId)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | POST /creator-marketplace/engagements/{id}/payment/phonepe/initiate
    | Creates a PhonePe order for a creator engagement payment.
    |--------------------------------------------------------------------------
    */
    public function initiate(Request $request, $engagementId): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = $this->getFirmProfile($user->id);

            if (!$firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $engagement = DB::table('creator_engagements')
                ->where('id', $engagementId)
                ->where('firm_id', $firmProfile->id)
                ->first();

            if (!$engagement) {
                return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
            }

            if ($engagement->status !== 'awaiting_payment') {
                return response()->json(['status' => false, 'message' => 'Payment not required for this engagement'], 422);
            }

            // Resolve the admin-selected active gateway; stamped onto the row so
            // verify/webhook use the same gateway even if the default changes.
            $gateway     = $this->payments->active();
            $gatewayName = $gateway->name();

            DB::beginTransaction();

            // Remove stale pending records for this engagement on this gateway
            DB::table('creator_engagement_payments')
                ->where('engagement_id', $engagementId)
                ->where('payment_method', $gatewayName)
                ->where('status', 'pending')
                ->delete();

            // Block if a payment is already under review or verified
            $blocking = DB::table('creator_engagement_payments')
                ->where('engagement_id', $engagementId)
                ->whereIn('status', ['awaiting_verification', 'verified', 'escrow_held'])
                ->first();

            if ($blocking) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'A payment is already in progress'], 422);
            }

            $amount    = (float) $engagement->accepted_bid_amount;
            $paymentId = DB::table('creator_engagement_payments')->insertGetId([
                'engagement_id'  => $engagementId,
                'firm_id'        => $firmProfile->id,
                'amount'         => $amount,
                'currency'       => 'INR',
                'payment_method' => $gatewayName,
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $merchantTxnId = 'ENG' . $engagementId . 'F' . $firmProfile->id . 'T' . time();

            $frontendUrl = rtrim(config('services.phonepe.frontend_url', config('app.url')), '/');
            $redirectUrl = $frontendUrl . '/creator-marketplace/payment/' . $engagementId . '?phonepe_txn=' . $merchantTxnId;

            $order = $gateway->createOrder($amount, $merchantTxnId, [
                'redirect_url'   => $redirectUrl,
                'callback_url'   => url('/api/creator-marketplace/payments/' . $gatewayName . '/webhook'),
                'customer_id'    => 'f' . $firmProfile->id,
                'customer_name'  => $user->name ?? '',
                'customer_email' => $user->email ?? '',
                'customer_phone' => $user->phone ?? '',
            ]);

            DB::table('creator_engagement_payments')
                ->where('id', $paymentId)
                ->update([
                    'gateway_order_id' => $merchantTxnId,
                    'updated_at'       => now(),
                ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'data'   => [
                    'gateway'               => $order['gateway'],
                    'redirect_url'          => $order['redirect_url'],
                    'payment_session_id'    => $order['payment_session_id'],
                    'mode'                  => $order['mode'],
                    'transaction_id'        => $merchantTxnId,
                    'engagement_payment_id' => $paymentId,
                    'amount'                => $amount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePeEngagementController@initiate: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Could not create payment order'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /creator-marketplace/engagements/{id}/payment/phonepe/verify
    | Verifies PhonePe payment and puts funds in escrow (activates engagement).
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request, $engagementId): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = $this->getFirmProfile($user->id);

            if (!$firmProfile) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
            }

            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $merchantTxnId = $request->transaction_id;

            $payment = DB::table('creator_engagement_payments')
                ->where('engagement_id', $engagementId)
                ->where('firm_id', $firmProfile->id)
                ->where('gateway_order_id', $merchantTxnId)
                ->whereNotIn('payment_method', ['manual'])
                ->first();

            if (!$payment) {
                return response()->json(['status' => false, 'message' => 'Payment record not found'], 404);
            }

            // Idempotency — already in escrow
            if ($payment->status === 'escrow_held') {
                return response()->json(['status' => true, 'message' => 'Payment already processed']);
            }

            // Resolve the gateway this order was created on (not the active one),
            // get the server-side normalized status, then settle via the shared
            // row-locked, idempotent, amount-verified service.
            $result  = $this->payments->gateway($payment->payment_method)->verifyPayment($merchantTxnId);
            $outcome = $this->settlement->settle($payment, $result);

            if ($outcome === 'held' || $outcome === 'already') {
                return response()->json(['status' => true, 'message' => 'Payment verified. Project is now active!']);
            }

            return response()->json([
                'status'  => false,
                'message' => $outcome === 'pending'
                    ? 'Payment is still being processed. Please wait a moment and refresh.'
                    : 'Payment was not successful.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePeEngagementController@verify: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Verification error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /creator-marketplace/payments/phonepe/webhook  [no auth — PhonePe S2S]
    |--------------------------------------------------------------------------
    */
    public function webhook(Request $request, string $gateway = 'phonepe'): JsonResponse
    {
        try {
            // Verify signature + normalize via the gateway this endpoint serves.
            try {
                $result = $this->payments->gateway($gateway)
                    ->parseWebhook($request->getContent(), $request->headers->all());
            } catch (\RuntimeException $e) {
                Log::warning("PhonePeEngagementController {$gateway} webhook: " . $e->getMessage());
                return response()->json(['message' => 'Invalid signature'], 401);
            }

            $merchantTxnId = $result['order_id'] ?? null;
            if (!$merchantTxnId) {
                return response()->json(['message' => 'Missing order id'], 400);
            }

            $payment = DB::table('creator_engagement_payments')
                ->where('gateway_order_id', $merchantTxnId)
                ->where('payment_method', $gateway)
                ->first();

            if (!$payment) {
                // Not a creator engagement order for this gateway — acknowledge.
                return response()->json(['message' => 'OK'], 200);
            }

            // Row-locked, idempotent, amount-verified settlement shared with verify().
            $this->settlement->settle($payment, $result);

            return response()->json(['message' => 'OK'], 200);
        } catch (\Exception $e) {
            Log::error('PhonePeEngagementController@webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
