<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Payment\PhonePeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ReferralHelper;
use App\Helpers\AuthHelper;
use App\Helpers\PremiumActivationEmailHelper;
use App\Helpers\FirmActivityHelper;
use App\Helpers\PlanHelper;
use App\Services\ActivityTracker;
use App\Enums\ActivityType;

class PhonePeFirmController extends Controller
{
    // Plan pricing/duration now come from the admin-managed
    // firm_subscription_plans catalog via PlanHelper (2026-07-11). No hardcoded
    // amounts — deactivated plans are no longer purchasable.

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

            // Remove stale pending PhonePe subscription records for this firm
            DB::table('firm_subscriptions')
                ->where('firm_id', $firmProfile->id)
                ->where('payment_gateway', 'phonepe')
                ->where('status', 'pending')
                ->delete();

            DB::beginTransaction();

            $subscriptionId = DB::table('firm_subscriptions')->insertGetId([
                'firm_id'         => $firmProfile->id,
                'contact_person'  => $user->name ?? null,
                'plan'            => $planId,
                'amount'          => $amount,
                'currency'        => 'INR',
                'payment_gateway' => 'phonepe',
                'payment_status'  => 'pending',
                'status'          => 'pending',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            $merchantTxnId = 'FRM' . $firmProfile->id . 'S' . $subscriptionId . 'T' . time();

            $frontendUrl = rtrim(config('services.phonepe.frontend_url', config('app.url')), '/');
            $redirectUrl = $frontendUrl . '/firm/payments?phonepe_txn=' . $merchantTxnId;

            $gateway = new PhonePeGateway();
            $order   = $gateway->createOrder($amount, $merchantTxnId, [
                'redirect_url' => $redirectUrl,
            ]);

            DB::table('firm_subscriptions')
                ->where('id', $subscriptionId)
                ->update([
                    'gateway_order_id' => $merchantTxnId,
                    'updated_at'       => now(),
                ]);

            DB::table('payment_logs')->insert([
                'firm_subscription_id' => $subscriptionId,
                'event_type'           => 'phonepe_order_created',
                'payload'              => json_encode(['merchant_txn_id' => $merchantTxnId, 'amount' => $amount]),
                'created_at'           => now(),
            ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'PhonePe order created',
                'data'    => [
                    'redirect_url'    => $order['redirect_url'],
                    'transaction_id'  => $merchantTxnId,
                    'subscription_id' => $subscriptionId,
                    'amount'          => $amount,
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
                ->where('payment_gateway', 'phonepe')
                ->first();

            if (!$subscription) {
                return response()->json(['status' => false, 'message' => 'Subscription not found'], 404);
            }

            // Idempotency — already activated
            if ($subscription->payment_status === 'paid') {
                return response()->json(['status' => true, 'message' => 'Payment already verified']);
            }

            $gateway    = new PhonePeGateway();
            $statusData = $gateway->fetchPayment($merchantTxnId);
            $isSuccess  = ($statusData['state'] ?? '') === 'COMPLETED';

            $gatewayPaymentId = $statusData['paymentDetails'][0]['transactionId'] ?? null;

            // Renewal-aware expiry: extend from the firm's current active expiry
            // when it is still in the future, else from now. Excludes THIS pending
            // row (not yet active) so it never counts as its own base.
            $currentExpiry = PlanHelper::currentActiveExpiry((int) $firmProfile->id);
            $expiresAt = PlanHelper::computeExpiry($subscription->plan, $currentExpiry);

            DB::beginTransaction();

            if ($isSuccess) {
                DB::table('firm_subscriptions')
                    ->where('id', $subscription->id)
                    ->update([
                        'gateway_payment_id' => $gatewayPaymentId,
                        'razorpay_response'  => json_encode($statusData),
                        'payment_status'     => 'paid',
                        'status'             => 'active',
                        'payment_date'       => now(),
                        'starts_at'          => now(),
                        'expires_at'         => $expiresAt,
                        'updated_at'         => now(),
                    ]);

                // Supersede any OTHER active subscription rows for this firm so the
                // renewal's extended expiry is the single source of truth (their
                // remaining validity was already folded into $expiresAt above).
                DB::table('firm_subscriptions')
                    ->where('firm_id', $firmProfile->id)
                    ->where('id', '!=', $subscription->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired', 'updated_at' => now()]);

                DB::table('firm_profiles')
                    ->where('id', $firmProfile->id)
                    ->update(['is_premium' => 1, 'updated_at' => now()]);

                // Firm referral: create a pending ₹2,000 payout if this firm was referred.
                ReferralHelper::onFirmPremiumActivated((int) $firmProfile->id);

                DB::table('payment_logs')->insert([
                    'firm_subscription_id' => $subscription->id,
                    'event_type'           => 'phonepe_payment_verified',
                    'payload'              => json_encode(['merchant_txn_id' => $merchantTxnId, 'gateway_payment_id' => $gatewayPaymentId]),
                    'created_at'           => now(),
                ]);

                DB::commit();

                // Activity log (async, non-blocking — never affects subscription activation).
                ActivityTracker::log(ActivityTracker::FIRM, $user->id, ActivityType::SUBSCRIPTION_PURCHASED, [
                    'subscription_id' => (int) $subscription->id,
                    'plan'            => $subscription->plan,
                    'amount'          => (float) $subscription->amount,
                ]);
                // Firm Activity Center feed (non-blocking).
                FirmActivityHelper::log($firmProfile->id, FirmActivityHelper::PREMIUM_PURCHASED, 'Purchased Premium (' . $subscription->plan . ')');
                // Activation confirmation email (non-blocking, exactly-once vs webhook).
                PremiumActivationEmailHelper::send((int) $subscription->id, PremiumActivationEmailHelper::TYPE_PHONEPE);

                return response()->json(['status' => true, 'message' => 'Payment verified successfully']);
            }

            DB::table('firm_subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'payment_status'    => 'failed',
                    'razorpay_response' => json_encode($statusData),
                    'updated_at'        => now(),
                ]);

            DB::commit();
            return response()->json([
                'status'  => false,
                'message' => 'Payment was not successful. State: ' . ($statusData['state'] ?? 'UNKNOWN'),
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
    public function webhook(Request $request)
    {
        try {
            $authorization = $request->header('Authorization') ?? '';
            $gateway       = new PhonePeGateway();

            if (!$gateway->verifySignature(['authorization' => $authorization])) {
                Log::warning('PhonePeFirmController webhook: signature verification failed');
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            $body    = $request->all();
            $payload = $body['payload'] ?? [];

            if (empty($payload)) {
                return response()->json(['message' => 'Bad request'], 400);
            }

            $merchantTxnId = $payload['merchantOrderId'] ?? null;
            if (!$merchantTxnId) {
                return response()->json(['message' => 'Missing merchantOrderId'], 400);
            }

            $subscription = DB::table('firm_subscriptions')
                ->where('gateway_order_id', $merchantTxnId)
                ->where('payment_gateway', 'phonepe')
                ->first();

            if (!$subscription) {
                Log::warning("PhonePeFirmController webhook: no subscription for txn {$merchantTxnId}");
                return response()->json(['message' => 'Not found'], 404);
            }

            if ($subscription->payment_status === 'paid') {
                return response()->json(['message' => 'Already processed'], 200);
            }

            $isSuccess        = ($payload['state'] ?? '') === 'COMPLETED';
            $gatewayPaymentId = $payload['paymentDetails'][0]['transactionId'] ?? null;

            // Renewal-aware expiry (same rule as verify()): extend from the firm's
            // current active expiry when still in the future, else from now.
            $currentExpiry = PlanHelper::currentActiveExpiry((int) $subscription->firm_id);
            $expiresAt = PlanHelper::computeExpiry($subscription->plan, $currentExpiry);

            DB::beginTransaction();

            if ($isSuccess) {
                DB::table('firm_subscriptions')
                    ->where('id', $subscription->id)
                    ->update([
                        'gateway_payment_id' => $gatewayPaymentId,
                        'razorpay_response'  => json_encode($body),
                        'payment_status'     => 'paid',
                        'status'             => 'active',
                        'payment_date'       => now(),
                        'starts_at'          => now(),
                        'expires_at'         => $expiresAt,
                        'updated_at'         => now(),
                    ]);

                // Supersede any OTHER active rows (their validity is folded in above).
                DB::table('firm_subscriptions')
                    ->where('firm_id', $subscription->firm_id)
                    ->where('id', '!=', $subscription->id)
                    ->where('status', 'active')
                    ->update(['status' => 'expired', 'updated_at' => now()]);

                DB::table('firm_profiles')
                    ->where('id', $subscription->firm_id)
                    ->update(['is_premium' => 1, 'updated_at' => now()]);

                // Firm referral: create a pending ₹2,000 payout if this firm was referred.
                ReferralHelper::onFirmPremiumActivated((int) $subscription->firm_id);

                DB::commit();

                // Activity log (async, non-blocking). No auth context in a S2S
                // webhook, so resolve the firm's account id from firm_profiles.
                $firmUserId = DB::table('firm_profiles')->where('id', $subscription->firm_id)->value('user_id');
                ActivityTracker::log(ActivityTracker::FIRM, $firmUserId, ActivityType::SUBSCRIPTION_PURCHASED, [
                    'subscription_id' => (int) $subscription->id,
                    'plan'            => $subscription->plan,
                    'amount'          => (float) $subscription->amount,
                ]);
                // Firm Activity Center feed (non-blocking).
                FirmActivityHelper::log($subscription->firm_id, FirmActivityHelper::PREMIUM_PURCHASED, 'Purchased Premium (' . $subscription->plan . ')');
                // Activation confirmation email (non-blocking, exactly-once vs verify()).
                PremiumActivationEmailHelper::send((int) $subscription->id, PremiumActivationEmailHelper::TYPE_PHONEPE);
            } else {
                DB::table('firm_subscriptions')
                    ->where('id', $subscription->id)
                    ->update([
                        'payment_status'    => 'failed',
                        'razorpay_response' => json_encode($body),
                        'updated_at'        => now(),
                    ]);
                DB::commit();
            }

            return response()->json(['message' => 'OK'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePeFirmController@webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
