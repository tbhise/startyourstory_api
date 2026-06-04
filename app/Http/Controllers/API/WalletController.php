<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\WalletHelper;
use Razorpay\Api\Api;

class WalletController extends Controller
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

    /*
    |--------------------------------------------------------------------------
    | GET /wallet — balance + recharge packs
    |--------------------------------------------------------------------------
    */
    public function getWallet(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $wallet = WalletHelper::getOrCreate($user->id);
            $packs  = DB::table('wallet_recharge_packs')
                ->where('is_active', 1)
                ->orderBy('sort_order')
                ->get();

            return response()->json([
                'status' => true,
                'data'   => [
                    'wallet' => [
                        'available_balance'       => (float) $wallet->available_balance,
                        'hold_balance'            => (float) $wallet->hold_balance,
                        'consumed_balance'        => (float) $wallet->consumed_balance,
                        'free_applications_used'  => (int)   $wallet->free_applications_used,
                        'free_applications_limit' => (int)   $wallet->free_applications_limit,
                        'free_remaining'          => max(0, $wallet->free_applications_limit - $wallet->free_applications_used),
                    ],
                    'packs'  => $packs,
                    'application_fee' => WalletHelper::APPLICATION_FEE,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('WalletController@getWallet: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /wallet/ledger — paginated transaction history
    |--------------------------------------------------------------------------
    */
    public function getLedger(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $perPage = 20;
            $page    = max(1, (int) $request->input('page', 1));

            $total = DB::table('wallet_transactions')->where('user_id', $user->id)->count();

            $transactions = DB::table('wallet_transactions')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->skip(($page - 1) * $perPage)
                ->take($perPage)
                ->get();

            return response()->json([
                'status' => true,
                'data'   => [
                    'transactions' => $transactions,
                    'total'        => $total,
                    'page'         => $page,
                    'per_page'     => $perPage,
                    'has_more'     => ($page * $perPage) < $total,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('WalletController@getLedger: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /wallet/recharges — recharge history
    |--------------------------------------------------------------------------
    */
    public function getRechargeHistory(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $recharges = DB::table('wallet_recharges')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(function ($item) {
                    $item->screenshot_url = $item->screenshot_url
                        ? asset('storage/' . $item->screenshot_url)
                        : null;
                    return $item;
                });

            return response()->json(['status' => true, 'data' => $recharges]);
        } catch (\Exception $e) {
            Log::error('WalletController@getRechargeHistory: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /wallet/recharge/order — create Razorpay order
    |--------------------------------------------------------------------------
    */
    public function createOrder(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:1',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            $amount = (float) $request->amount;

            // Remove stale pending Razorpay orders for this user
            DB::table('wallet_recharges')
                ->where('user_id', $user->id)
                ->where('payment_method', 'razorpay')
                ->where('status', 'pending')
                ->delete();

            $rechargeId = DB::table('wallet_recharges')->insertGetId([
                'user_id'        => $user->id,
                'amount'         => $amount,
                'payment_method' => 'razorpay',
                'payment_status' => 'pending',
                'status'         => 'pending',
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);

            $api   = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $order = $api->order->create([
                'receipt'         => 'wallet_' . $user->id . '_' . $rechargeId,
                'amount'          => (int) ($amount * 100),
                'currency'        => 'INR',
                'payment_capture' => 1,
            ]);

            DB::table('wallet_recharges')
                ->where('id', $rechargeId)
                ->update(['gateway_order_id' => $order['id'], 'updated_at' => now()]);

            return response()->json([
                'status'  => true,
                'message' => 'Order created',
                'data'    => [
                    'id'          => $order['id'],
                    'amount'      => $order['amount'],
                    'currency'    => $order['currency'],
                    'recharge_id' => $rechargeId,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('WalletController@createOrder: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /wallet/recharge/verify — verify Razorpay payment + credit wallet
    |--------------------------------------------------------------------------
    */
    public function verifyPayment(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $api = new Api(config('services.razorpay.key'), config('services.razorpay.secret'));
            $api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature'  => $request->razorpay_signature,
            ]);

            $recharge = DB::table('wallet_recharges')
                ->where('gateway_order_id', $request->razorpay_order_id)
                ->first();

            if (!$recharge) {
                return response()->json(['status' => false, 'message' => 'Recharge record not found'], 404);
            }
            if ($recharge->payment_status === 'paid') {
                return response()->json(['status' => true, 'message' => 'Already processed']);
            }

            $payment = $api->payment->fetch($request->razorpay_payment_id);

            DB::table('wallet_recharges')
                ->where('id', $recharge->id)
                ->update([
                    'gateway_payment_id'  => $request->razorpay_payment_id,
                    'gateway_signature'   => $request->razorpay_signature,
                    'payment_status'      => 'paid',
                    'status'              => 'approved',
                    'payment_method_used' => $payment['method'] ?? null,
                    'razorpay_response'   => json_encode($payment->toArray()),
                    'approved_at'         => now(),
                    'updated_at'          => now(),
                ]);

            WalletHelper::credit($user->id, (float) $recharge->amount, $recharge->id);

            $wallet = WalletHelper::getOrCreate($user->id);

            return response()->json([
                'status'  => true,
                'message' => "₹{$recharge->amount} added to your wallet",
                'data'    => [
                    'available_balance' => (float) $wallet->available_balance,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('WalletController@verifyPayment: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Payment verification failed'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /wallet/recharge/manual — submit manual payment proof
    |--------------------------------------------------------------------------
    */
    public function submitManualRecharge(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $validator = Validator::make($request->all(), [
                'amount'           => 'required|numeric|min:1',
                'payment_date'     => 'required|date',
                'utr_number'       => 'nullable|string|max:100',
                'reference_number' => 'nullable|string|max:100',
                'screenshot'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            $screenshotUrl = null;
            if ($request->hasFile('screenshot')) {
                $file          = $request->file('screenshot');
                $screenshotUrl = $file->storeAs(
                    'wallet_screenshots',
                    time() . '_' . $user->id . '.' . $file->getClientOriginalExtension(),
                    'public'
                );
            }

            $rechargeId = DB::table('wallet_recharges')->insertGetId([
                'user_id'          => $user->id,
                'amount'           => (float) $request->amount,
                'payment_method'   => 'manual',
                'status'           => 'pending',
                'utr_number'       => $request->utr_number,
                'reference_number' => $request->reference_number,
                'screenshot_url'   => $screenshotUrl,
                'payment_date'     => $request->payment_date,
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Payment proof submitted. Admin will review within 24 hours.',
                'data'    => ['recharge_id' => $rechargeId],
            ]);
        } catch (\Exception $e) {
            Log::error('WalletController@submitManualRecharge: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
