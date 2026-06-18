<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Payment\PhonePeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PhonePeEngagementController extends Controller
{
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

            DB::beginTransaction();

            // Remove stale pending PhonePe records for this engagement
            DB::table('creator_engagement_payments')
                ->where('engagement_id', $engagementId)
                ->where('payment_method', 'phonepe')
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
                'payment_method' => 'phonepe',
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $merchantTxnId = 'ENG' . $engagementId . 'F' . $firmProfile->id . 'T' . time();

            $frontendUrl = rtrim(config('services.phonepe.frontend_url', config('app.url')), '/');
            $redirectUrl = $frontendUrl . '/creator-marketplace/payment/' . $engagementId . '?phonepe_txn=' . $merchantTxnId;

            $gateway = new PhonePeGateway();
            $order   = $gateway->createOrder($amount, $merchantTxnId, [
                'redirect_url' => $redirectUrl,
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
                    'redirect_url'        => $order['redirect_url'],
                    'transaction_id'      => $merchantTxnId,
                    'engagement_payment_id' => $paymentId,
                    'amount'              => $amount,
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
                ->where('payment_method', 'phonepe')
                ->first();

            if (!$payment) {
                return response()->json(['status' => false, 'message' => 'Payment record not found'], 404);
            }

            // Idempotency — already in escrow
            if ($payment->status === 'escrow_held') {
                return response()->json(['status' => true, 'message' => 'Payment already processed']);
            }

            $gateway    = new PhonePeGateway();
            $statusData = $gateway->fetchPayment($merchantTxnId);
            $isSuccess  = ($statusData['state'] ?? '') === 'COMPLETED';

            $gatewayPaymentId = $statusData['paymentDetails'][0]['transactionId'] ?? null;

            DB::beginTransaction();

            if ($isSuccess) {
                DB::table('creator_engagement_payments')
                    ->where('id', $payment->id)
                    ->update([
                        'status'             => 'escrow_held',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'gateway_response'   => json_encode($statusData),
                        'updated_at'         => now(),
                    ]);

                DB::table('creator_engagements')
                    ->where('id', $engagementId)
                    ->update(['status' => 'active', 'updated_at' => now()]);

                // Notify creator
                $engagement = DB::table('creator_engagements')->where('id', $engagementId)->first();
                $project    = DB::table('creator_projects')->where('id', $engagement->creator_requirement_id)->first();

                DB::table('creator_marketplace_notifications')->insert([
                    'user_id'    => $engagement->creator_id,
                    'type'       => 'payment_received',
                    'title'      => 'Payment received — project is now active!',
                    'body'       => "The firm has paid for \"{$project->title}\". Your project is now active.",
                    'data'       => json_encode(['engagement_id' => (int) $engagementId]),
                    'read_at'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                return response()->json(['status' => true, 'message' => 'Payment verified. Project is now active!']);
            }

            DB::table('creator_engagement_payments')
                ->where('id', $payment->id)
                ->update([
                    'status'           => 'pending',
                    'gateway_response' => json_encode($statusData),
                    'updated_at'       => now(),
                ]);

            DB::commit();
            return response()->json([
                'status'  => false,
                'message' => 'Payment was not successful. State: ' . ($statusData['state'] ?? 'UNKNOWN'),
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
    public function webhook(Request $request): JsonResponse
    {
        try {
            $authorization = $request->header('Authorization') ?? '';
            $gateway       = new PhonePeGateway();

            if (!$gateway->verifySignature(['authorization' => $authorization])) {
                Log::warning('PhonePeEngagementController webhook: signature verification failed');
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

            $payment = DB::table('creator_engagement_payments')
                ->where('gateway_order_id', $merchantTxnId)
                ->where('payment_method', 'phonepe')
                ->first();

            if (!$payment) {
                Log::warning("PhonePeEngagementController webhook: no payment for txn {$merchantTxnId}");
                return response()->json(['message' => 'Not found'], 404);
            }

            if ($payment->status === 'escrow_held') {
                return response()->json(['message' => 'Already processed'], 200);
            }

            $isSuccess        = ($payload['state'] ?? '') === 'COMPLETED';
            $gatewayPaymentId = $payload['paymentDetails'][0]['transactionId'] ?? null;

            DB::beginTransaction();

            if ($isSuccess) {
                DB::table('creator_engagement_payments')
                    ->where('id', $payment->id)
                    ->update([
                        'status'             => 'escrow_held',
                        'gateway_payment_id' => $gatewayPaymentId,
                        'gateway_response'   => json_encode($body),
                        'updated_at'         => now(),
                    ]);

                DB::table('creator_engagements')
                    ->where('id', $payment->engagement_id)
                    ->update(['status' => 'active', 'updated_at' => now()]);

                $engagement = DB::table('creator_engagements')->where('id', $payment->engagement_id)->first();
                $project    = DB::table('creator_projects')->where('id', $engagement->creator_requirement_id)->first();

                DB::table('creator_marketplace_notifications')->insert([
                    'user_id'    => $engagement->creator_id,
                    'type'       => 'payment_received',
                    'title'      => 'Payment received — project is now active!',
                    'body'       => "The firm has paid for \"{$project->title}\". Your project is now active.",
                    'data'       => json_encode(['engagement_id' => (int) $payment->engagement_id]),
                    'read_at'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
            } else {
                DB::commit();
            }

            return response()->json(['message' => 'OK'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePeEngagementController@webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
