<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\Settlement\WalletSettlementService;
use App\Helpers\WalletHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\AuthHelper;

class PhonePeWalletController extends Controller
{
    public function __construct(
        private PaymentManager $payments,
        private WalletSettlementService $settlement,
    ) {}

    private function getStudent(Request $request): ?object
    {
        $user = AuthHelper::resolveUser($request);
        return ($user && $user->role === 'student') ? $user : null;
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

            // Resolve the admin-selected active gateway. The gateway name is
            // stamped onto the row so verify/webhook use the SAME gateway even if
            // the admin switches the default afterwards.
            $gateway     = $this->payments->active();
            $gatewayName = $gateway->name();

            // Remove stale pending orders for this user on the active gateway.
            DB::table('wallet_recharges')
                ->where('user_id', $user->id)
                ->where('payment_method', $gatewayName)
                ->where('status', 'pending')
                ->delete();

            DB::beginTransaction();
            $rechargeId = DB::table('wallet_recharges')->insertGetId([
                'user_id'        => $user->id,
                'amount'         => $amount,
                'payment_method' => $gatewayName,
                'payment_status' => 'pending',
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            // merchantTransactionId — must be unique, alphanumeric, max 38 chars
            $merchantTxnId = 'WLT' . $user->id . 'R' . $rechargeId . 'T' . time();

            $frontendUrl  = rtrim(config('services.phonepe.frontend_url', config('app.url')), '/');
            $redirectUrl  = $frontendUrl . '/wallet/recharge?phonepe_txn=' . $merchantTxnId;
            $callbackUrl  = url('/api/wallet/recharge/' . $gatewayName . '/webhook');

            $order = $gateway->createOrder($amount, $merchantTxnId, [
                'user_id'        => $user->id,
                'redirect_url'   => $redirectUrl,
                'callback_url'   => $callbackUrl,
                'customer_id'    => 'u' . $user->id,
                'customer_name'  => $user->name ?? '',
                'customer_email' => $user->email ?? '',
                'customer_phone' => $user->phone ?? '',
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
                'message' => 'Payment order created',
                'data'    => [
                    'gateway'            => $order['gateway'],
                    'redirect_url'       => $order['redirect_url'],
                    'payment_session_id' => $order['payment_session_id'],
                    'mode'               => $order['mode'],
                    'transaction_id'     => $merchantTxnId,
                    'recharge_id'        => $rechargeId,
                    'amount'             => $amount,
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
                ->whereNotIn('payment_method', ['manual'])
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

            // Resolve the gateway the order was created with (NOT the active one)
            // and get the server-side, normalized status. Never trust the client.
            $gateway = $this->payments->gateway($recharge->payment_method);
            $result  = $gateway->verifyPayment($merchantTxnId);

            // Row-locked, idempotent, amount-verified settlement shared with the
            // webhook. Outcome: 'credited' | 'already' | 'failed' | 'pending'.
            $outcome = $this->settlement->settle($recharge, $result);

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
                'message' => $outcome === 'pending'
                    ? 'Payment is still being processed. Please wait a moment and refresh.'
                    : 'Payment was not successful.',
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
    public function webhook(Request $request, string $gateway = 'phonepe')
    {
        try {
            // Verify signature + normalize the payload via the specific gateway
            // this endpoint serves. parseWebhook() fails closed (throws) on a bad
            // signature.
            $gw = $this->payments->gateway($gateway);

            try {
                $result = $gw->parseWebhook($request->getContent(), $request->headers->all());
            } catch (\RuntimeException $e) {
                Log::warning("Wallet {$gateway} webhook: " . $e->getMessage());
                return response()->json(['message' => 'Invalid signature'], 401);
            }

            $merchantTxnId = $result['order_id'] ?? null;
            if (!$merchantTxnId) {
                return response()->json(['message' => 'Missing order id'], 400);
            }

            $recharge = DB::table('wallet_recharges')
                ->where('gateway_order_id', $merchantTxnId)
                ->where('payment_method', $gateway)
                ->first();

            if (!$recharge) {
                // Not a wallet order for this gateway (e.g. another domain) — ack.
                return response()->json(['message' => 'OK'], 200);
            }

            // Row-locked, idempotent, amount-verified settlement shared with verify().
            $this->settlement->settle($recharge, $result);

            return response()->json(['message' => 'OK'], 200);
        } catch (\Exception $e) {
            Log::error('PhonePeWalletController@webhook: ' . $e->getMessage());
            return response()->json(['message' => 'Internal error'], 500);
        }
    }
}
