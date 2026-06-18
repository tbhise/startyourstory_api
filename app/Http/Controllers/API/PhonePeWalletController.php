<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Payment\PhonePeGateway;
use App\Helpers\WalletHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PhonePeWalletController extends Controller
{
    private function getStudent(Request $request): ?object
    {
        $token = $request->cookie('auth_token');
        if (!$token) return null;
        return DB::table('users')
            ->where('api_token', $token)
            ->where('is_deleted', false)
            ->where('role', 'student')
            ->first();
    }

    private function creatorGuard(object $user): ?\Illuminate\Http\JsonResponse
    {
        $lf = DB::table('student_profiles')->where('user_id', $user->id)->value('looking_for');
        if ($lf === 'creator') {
            return response()->json(['status' => false, 'message' => 'Creators cannot access the student wallet.'], 403);
        }
        return null;
    }

    /*
    |--------------------------------------------------------------------------
    | POST /wallet/recharge/phonepe/initiate  [auth required]
    | Creates a PhonePe order and returns the checkout redirect URL.
    |--------------------------------------------------------------------------
    */
    public function initiate(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            if ($err = $this->creatorGuard($user)) return $err;

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            $amount = (float) $request->amount;

            // Remove stale pending PhonePe orders for this user
            DB::table('wallet_recharges')
                ->where('user_id', $user->id)
                ->where('payment_method', 'phonepe')
                ->where('status', 'pending')
                ->delete();

            DB::beginTransaction();
            $rechargeId = DB::table('wallet_recharges')->insertGetId([
                'user_id'        => $user->id,
                'amount'         => $amount,
                'payment_method' => 'phonepe',
                'payment_status' => 'pending',
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // merchantTransactionId — must be unique, alphanumeric, max 38 chars
            $merchantTxnId = 'WLT' . $user->id . 'R' . $rechargeId . 'T' . time();

            $frontendUrl  = rtrim(config('services.phonepe.frontend_url', config('app.url')), '/');
            $redirectUrl  = $frontendUrl . '/wallet/recharge?phonepe_txn=' . $merchantTxnId;
            $callbackUrl  = url('/api/wallet/recharge/phonepe/webhook');

            $gateway = new PhonePeGateway();
            $order   = $gateway->createOrder($amount, $merchantTxnId, [
                'user_id'      => $user->id,
                'redirect_url' => $redirectUrl,
                'callback_url' => $callbackUrl,
            ]);

            DB::table('wallet_recharges')
                ->where('id', $rechargeId)
                ->update([
                    'gateway_order_id' => $merchantTxnId,
                    'gateway_response' => json_encode($order['raw'] ?? []),
                    'updated_at'       => now(),
                ]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'PhonePe order created',
                'data'    => [
                    'redirect_url'   => $order['redirect_url'],
                    'transaction_id' => $merchantTxnId,
                    'recharge_id'    => $rechargeId,
                    'amount'         => $amount,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePeWalletController@initiate: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to initiate PhonePe payment'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /wallet/recharge/phonepe/verify  [auth required]
    | Frontend calls this after PhonePe redirects the user back.
    | Verifies payment status with PhonePe API and credits wallet.
    |--------------------------------------------------------------------------
    */
    public function verify(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            if ($err = $this->creatorGuard($user)) return $err;

            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            $merchantTxnId = $request->transaction_id;

            $recharge = DB::table('wallet_recharges')
                ->where('gateway_order_id', $merchantTxnId)
                ->where('user_id', $user->id)
                ->where('payment_method', 'phonepe')
                ->first();

            if (!$recharge) {
                return response()->json(['status' => false, 'message' => 'Transaction not found'], 404);
            }

            // Idempotency — already credited (fast path; re-checked under lock below)
            if ($recharge->payment_status === 'paid') {
                $wallet = WalletHelper::getOrCreate($user->id);
                return response()->json([
                    'status'  => true,
                    'message' => 'Payment already processed',
                    'data'    => ['available_balance' => (float) $wallet->available_balance],
                ]);
            }

            // Query PhonePe status API — never trust client-side params
            $gateway     = new PhonePeGateway();
            $statusData  = $gateway->fetchPayment($merchantTxnId);
            $isSuccess   = ($statusData['state'] ?? '') === 'COMPLETED';

            $gatewayPaymentId = $statusData['paymentDetails'][0]['transactionId'] ?? null;

            // Mutate atomically under a row lock so verify() racing the S2S webhook
            // (or a double-submitted verify) can never double-credit. Outcome:
            // 'credited' | 'already' | 'failed'.
            $outcome = DB::transaction(function () use ($recharge, $isSuccess, $gatewayPaymentId, $statusData) {
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
                            'gateway_response'   => json_encode($statusData),
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
                        'gateway_response' => json_encode($statusData),
                        'rejected_at'      => now(),
                        'updated_at'       => now(),
                    ]);
                return 'failed';
            });

            if ($outcome === 'credited' || $outcome === 'already') {
                $wallet = WalletHelper::getOrCreate($user->id);
                return response()->json([
                    'status'  => true,
                    'message' => $outcome === 'credited'
                        ? "₹{$recharge->amount} added to your wallet"
                        : 'Payment already processed',
                    'data'    => ['available_balance' => (float) $wallet->available_balance],
                ]);
            }

            return response()->json([
                'status'  => false,
                'message' => 'Payment was not successful. State: ' . ($statusData['state'] ?? 'UNKNOWN'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePeWalletController@verify: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Payment verification failed'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /wallet/recharge/phonepe/webhook  [no auth — PhonePe S2S]
    | Server-to-server notification from PhonePe after payment completion.
    | Verifies X-VERIFY signature before processing.
    |--------------------------------------------------------------------------
    */
    public function webhook(Request $request)
    {
        try {
            // PhonePe v2 sends plain JSON body + Authorization: SHA256(username:password) header
            $authorization = $request->header('Authorization') ?? '';

            $gateway = new PhonePeGateway();
            if (!$gateway->verifySignature(['authorization' => $authorization])) {
                Log::warning('PhonePe webhook: signature verification failed');
                return response()->json(['message' => 'Invalid signature'], 403);
            }

            $body    = $request->all();
            $event   = $body['event'] ?? '';
            $payload = $body['payload'] ?? [];

            if (empty($payload)) {
                Log::warning('PhonePe webhook: empty payload');
                return response()->json(['message' => 'Bad request'], 400);
            }

            $merchantTxnId = $payload['merchantOrderId'] ?? null;

            if (!$merchantTxnId) {
                return response()->json(['message' => 'Missing merchantOrderId'], 400);
            }

            $recharge = DB::table('wallet_recharges')
                ->where('gateway_order_id', $merchantTxnId)
                ->where('payment_method', 'phonepe')
                ->first();

            if (!$recharge) {
                Log::warning("PhonePe webhook: no recharge found for txn {$merchantTxnId}");
                return response()->json(['message' => 'Not found'], 404);
            }

            $isSuccess        = ($payload['state'] ?? '') === 'COMPLETED';
            $gatewayPaymentId = $payload['paymentDetails'][0]['transactionId'] ?? null;

            // Credit atomically under a row lock so concurrent / duplicate / replayed
            // webhooks can never double-credit: the lock serializes them and the
            // re-checked payment_status='paid' guard makes repeats a no-op.
            $credited = DB::transaction(function () use ($recharge, $isSuccess, $gatewayPaymentId, $body) {
                $fresh = DB::table('wallet_recharges')
                    ->where('id', $recharge->id)
                    ->lockForUpdate()
                    ->first();

                // Idempotency — already credited by an earlier webhook or by verify().
                if ($fresh->payment_status === 'paid') {
                    return false;
                }

                if ($isSuccess) {
                    DB::table('wallet_recharges')
                        ->where('id', $fresh->id)
                        ->update([
                            'gateway_payment_id' => $gatewayPaymentId,
                            'payment_status'     => 'paid',
                            'status'             => 'approved',
                            'gateway_response'   => json_encode($body),
                            'approved_at'        => now(),
                            'updated_at'         => now(),
                        ]);

                    WalletHelper::credit($fresh->user_id, (float) $fresh->amount, $fresh->id);
                    return true;
                }

                DB::table('wallet_recharges')
                    ->where('id', $fresh->id)
                    ->update([
                        'payment_status'   => 'failed',
                        'status'           => 'rejected',
                        'gateway_response' => json_encode($body),
                        'rejected_at'      => now(),
                        'updated_at'       => now(),
                    ]);
                return false;
            });

            return response()->json(['message' => 'OK'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('PhonePeWalletController@webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
