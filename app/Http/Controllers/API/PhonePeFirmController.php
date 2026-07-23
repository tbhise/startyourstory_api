<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\Settlement\FirmPremiumSettlementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\AuthHelper;
use App\Helpers\PlanHelper;

class PhonePeFirmController extends Controller
{
    // Plan pricing/duration now come from the admin-managed
    // firm_subscription_plans catalog via PlanHelper (2026-07-11). No hardcoded
    // amounts — deactivated plans are no longer purchasable.

    public function __construct(
        private PaymentManager $payments,
        private FirmPremiumSettlementService $settlement,
    ) {}

    private function getFirmUser(Request $request): ?object
    {
        return AuthHelper::resolveUser($request);
    }

    private function getFirmProfile(int $userId): ?object
    {
        return DB::table('firm_profiles')->where('user_id', $userId)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | POST /payments/phonepe/initiate  [auth required]
    | Creates a PhonePe order for a firm subscription plan.
    |--------------------------------------------------------------------------
    */
    public function initiate(Request $request)
    {
        try {
            $user = $this->getFirmUser($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $firmProfile = $this->getFirmProfile($user->id);
            if (!$firmProfile) return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);

            $validator = Validator::make($request->all(), [
                'plan_id' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            $planId = $request->plan_id;
            if (!PlanHelper::isPurchasable($planId)) {
                return response()->json(['status' => false, 'message' => 'Invalid or unavailable plan selected'], 422);
            }

            // Price snapshot taken at purchase time (branch offices pay half).
            // Stored on the row so future catalog price changes never alter it.
            $amount = (int) round(PlanHelper::priceFor($planId, !empty($firmProfile->is_branch)));

            // Resolve the admin-selected active gateway; stamped onto the row so
            // verify/webhook use the same gateway even if the default changes.
            $gateway     = $this->payments->active();
            $gatewayName = $gateway->name();

            // Remove stale pending subscription records for this firm on this gateway
            DB::table('firm_subscriptions')
                ->where('firm_id', $firmProfile->id)
                ->where('payment_gateway', $gatewayName)
                ->where('status', 'pending')
                ->delete();

            DB::beginTransaction();

            $subscriptionId = DB::table('firm_subscriptions')->insertGetId([
                'firm_id'         => $firmProfile->id,
                'contact_person'  => $user->name ?? null,
                'plan'            => $planId,
                'amount'          => $amount,
                'currency'        => 'INR',
                'payment_gateway' => $gatewayName,
                'payment_status'  => 'pending',
                'status'          => 'pending',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $merchantTxnId = 'FRM' . $firmProfile->id . 'S' . $subscriptionId . 'T' . time();

            $frontendUrl = rtrim(config('services.phonepe.frontend_url', config('app.url')), '/');
            $redirectUrl = $frontendUrl . '/firm/payments?phonepe_txn=' . $merchantTxnId;

            $order = $gateway->createOrder($amount, $merchantTxnId, [
                'redirect_url'   => $redirectUrl,
                'callback_url'   => url('/api/payments/' . $gatewayName . '/webhook'),
                'customer_id'    => 'f' . $firmProfile->id,
                'customer_name'  => $user->name ?? '',
                'customer_email' => $user->email ?? '',
                'customer_phone' => $user->phone ?? '',
            ]);

            DB::table('firm_subscriptions')
                ->where('id', $subscriptionId)
                ->update([
                    'gateway_order_id' => $merchantTxnId,
                    'updated_at'       => now(),
                ]);

            DB::table('payment_logs')->insert([
                'firm_subscription_id' => $subscriptionId,
                'event_type'           => $gatewayName . '_order_created',
                'payload'              => json_encode(['merchant_txn_id' => $merchantTxnId, 'amount' => $amount]),
                'created_at'           => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Payment order created',
                'data'    => [
                    'gateway'            => $order['gateway'],
                    'redirect_url'       => $order['redirect_url'],
                    'payment_session_id' => $order['payment_session_id'],
                    'mode'               => $order['mode'],
                    'transaction_id'     => $merchantTxnId,
                    'subscription_id'    => $subscriptionId,
                    'amount'             => $amount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePeFirmController@initiate: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to initiate payment'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /payments/phonepe/verify  [auth required]
    | Verifies PhonePe payment status and activates the subscription.
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request)
    {
        try {
            $user = $this->getFirmUser($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $firmProfile = $this->getFirmProfile($user->id);
            if (!$firmProfile) return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);

            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            $merchantTxnId = $request->transaction_id;

            $subscription = DB::table('firm_subscriptions')
                ->where('gateway_order_id', $merchantTxnId)
                ->where('firm_id', $firmProfile->id)
                ->whereNotIn('payment_gateway', ['manual'])
                ->first();

            if (!$subscription) {
                return response()->json(['status' => false, 'message' => 'Subscription not found'], 404);
            }

            // Idempotency — already activated
            if ($subscription->payment_status === 'paid') {
                return response()->json(['status' => true, 'message' => 'Payment already verified']);
            }

            // Resolve the gateway this order was created on (not the active one),
            // get the server-side normalized status, then settle (row-locked,
            // idempotent, amount-verified) via the shared service.
            $result  = $this->payments->gateway($subscription->payment_gateway)->verifyPayment($merchantTxnId);
            $outcome = $this->settlement->settle($subscription, $result);

            if ($outcome === 'activated' || $outcome === 'already') {
                return response()->json(['status' => true, 'message' => 'Payment verified successfully']);
            }

            return response()->json([
                'status'  => false,
                'message' => $outcome === 'pending'
                    ? 'Payment is still being processed. Please wait a moment and refresh.'
                    : 'Payment was not successful.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePeFirmController@verify: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Payment verification failed'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /payments/phonepe/webhook  [no auth — PhonePe S2S]
    |--------------------------------------------------------------------------
    */
    public function webhook(Request $request, string $gateway = 'phonepe')
    {
        try {
            // Verify signature + normalize via the gateway this endpoint serves.
            try {
                $result = $this->payments->gateway($gateway)
                    ->parseWebhook($request->getContent(), $request->headers->all());
            } catch (\RuntimeException $e) {
                Log::warning("PhonePeFirmController {$gateway} webhook: " . $e->getMessage());
                return response()->json(['message' => 'Invalid signature'], 401);
            }

            $merchantTxnId = $result['order_id'] ?? null;
            if (!$merchantTxnId) {
                return response()->json(['message' => 'Missing order id'], 400);
            }

            $subscription = DB::table('firm_subscriptions')
                ->where('gateway_order_id', $merchantTxnId)
                ->where('payment_gateway', $gateway)
                ->first();

            if (!$subscription) {
                // Not a firm subscription order for this gateway — acknowledge.
                return response()->json(['message' => 'OK'], 200);
            }

            // Row-locked, idempotent, amount-verified settlement shared with verify().
            $this->settlement->settle($subscription, $result);

            return response()->json(['message' => 'OK'], 200);
        } catch (\Exception $e) {
            Log::error('PhonePeFirmController@webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
