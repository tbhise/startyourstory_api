<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function createOrder(Request $request)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | Get Auth User
        |--------------------------------------------------------------------------
        */
            $token = $request->cookie('auth_token');
            Log::info($token);
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            $userId = $user->id;
            /*
        |--------------------------------------------------------------------------
        | Get Firm Profile
        |--------------------------------------------------------------------------
        */
            $firmProfile = DB::table('firm_profiles')
                ->where('user_id', $userId)
                ->first();
            if (!$firmProfile) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found',
                ], 404);
            }
            /*
        |--------------------------------------------------------------------------
        | Get User Details
        |--------------------------------------------------------------------------
        */
            $user = DB::table('users')
                ->where('id', $userId)
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Validate Plan
        |--------------------------------------------------------------------------
        */
            $plan = $request->plan_id;
            $plans = [
                'premium-monthly' => [
                    'amount' => 499,
                    'days' => 30,
                ],
                'premium-quarterly' => [
                    'amount' => 1299,
                    'days' => 90,
                ],
                'premium-yearly' => [
                    'amount' => 9999,
                    'days' => 365,
                ],
            ];
            if (!isset($plans[$plan])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid plan selected',
                ], 422);
            }
            $amount = $plans[$plan]['amount'];


            /*
|--------------------------------------------------------------------------
| Remove Old Pending Razorpay Orders
|--------------------------------------------------------------------------
*/

            DB::table('firm_subscriptions')
                ->where('firm_id', $firmProfile->id)
                ->where('payment_gateway', 'razorpay')
                ->where('status', 'pending')
                ->delete();

            DB::beginTransaction();

            /*
        |--------------------------------------------------------------------------
        | Create Pending Subscription
        |--------------------------------------------------------------------------
        */
            $subscriptionId = DB::table('firm_subscriptions')->insertGetId([
                'firm_id' => $firmProfile->id,
                'contact_person' => $user->name ?? null,
                'plan' => $plan,
                'amount' => $amount,
                'currency' => 'INR',
                'payment_gateway' => 'razorpay',
                'payment_status' => 'pending',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            /*
        |--------------------------------------------------------------------------
        | Create Razorpay Order
        |--------------------------------------------------------------------------
        */
            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );
            $order = $api->order->create([
                'receipt' => 'firm_' . $firmProfile->id . '_sub_' . $subscriptionId,
                'amount' => $amount * 100,
                'currency' => 'INR',
                'payment_capture' => 1,
            ]);
            /*
        |--------------------------------------------------------------------------
        | Save Razorpay Order ID
        |--------------------------------------------------------------------------
        */
            DB::table('firm_subscriptions')
                ->where('id', $subscriptionId)
                ->update([
                    'gateway_order_id' => $order['id'],
                    'updated_at' => now(),
                ]);
            DB::table('payment_logs')->insert([
                'firm_subscription_id' => $subscriptionId,
                'event_type' => 'order_created',
                'payload' => json_encode([
                    'order_id' => $order['id'],
                    'amount' => $order['amount'],
                    'currency' => $order['currency'],
                ]),
                'created_at' => now(),
            ]);
            /*
        |--------------------------------------------------------------------------
        | Return Response
        |--------------------------------------------------------------------------
        */
            DB::commit();
            return response()->json([
                'status' => true,
                'id' => $order['id'],
                'amount' => $order['amount'],
                'currency' => $order['currency'],
                'subscription_id' => $subscriptionId,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create Order Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function verifyPayment(Request $request)
    {
        try {
            $attributes = [
                'razorpay_order_id' => $request->razorpay_order_id,
                'razorpay_payment_id' => $request->razorpay_payment_id,
                'razorpay_signature' => $request->razorpay_signature,
            ];
            $api = new Api(
                config('services.razorpay.key'),
                config('services.razorpay.secret')
            );
            /*
        |--------------------------------------------------------------------------
        | Verify Signature
        |--------------------------------------------------------------------------
        */
            $api->utility->verifyPaymentSignature($attributes);
            $payment = $api->payment->fetch(
                $request->razorpay_payment_id
            );
            /*
        |--------------------------------------------------------------------------
        | Find Subscription
        |--------------------------------------------------------------------------
        */
            $subscription = DB::table('firm_subscriptions')
                ->where(
                    'gateway_order_id',
                    $request->razorpay_order_id
                )
                ->first();
            if (!$subscription) {
                return response()->json([
                    'status' => false,
                    'message' => 'Subscription not found',
                ], 404);
            }
            /*
        |--------------------------------------------------------------------------
        | Prevent Duplicate Processing
        |--------------------------------------------------------------------------
        */
            if ($subscription->payment_status === 'paid') {
                return response()->json([
                    'status' => true,
                    'message' => 'Payment already verified',
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Calculate Expiry
        |--------------------------------------------------------------------------
        */
            $days = match ($subscription->plan) {
                'premium-monthly' => 30,
                'premium-quarterly' => 90,
                'premium-yearly' => 365,
                default => 30,
            };
            /*
        |--------------------------------------------------------------------------
        | Activate Subscription
        |--------------------------------------------------------------------------
        */
            DB::beginTransaction();
            DB::table('firm_subscriptions')
                ->where('id', $subscription->id)
                ->update([
                    'gateway_payment_id' =>
                    $request->razorpay_payment_id,
                    'gateway_signature' =>
                    $request->razorpay_signature,
                    'payment_method' =>
                    $payment['method'] ?? null,
                    'razorpay_response' => json_encode($payment->toArray()) ?? null,
                    'payment_status' => 'paid',
                    'status' => 'active',
                    'payment_date' => now(),
                    'starts_at' => now(),
                    'expires_at' => now()->addDays($days),
                    'updated_at' => now(),
                ]);

            DB::table('firm_profiles')
                ->where('id', $subscription->firm_id)
                ->update(['is_premium' => 1, 'updated_at' => now()]);
            /*
        |--------------------------------------------------------------------------
        | Log Success
        |--------------------------------------------------------------------------
        */
            DB::table('payment_logs')->insert([
                'firm_subscription_id' => $subscription->id,

                'event_type' => 'payment_verified',

                'payload' => json_encode([
                    'order_id' => $request->razorpay_order_id,
                    'payment_id' => $request->razorpay_payment_id,
                    'method' => $payment['method'] ?? null,
                    'amount' => $payment['amount'] ?? null,
                ]),

                'created_at' => now(),
            ]);


            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Payment verified successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Verify Payment Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Payment verification failed',
            ], 500);
        }
    }
    public function paymentFailure(Request $request)
    {
        try {

            $subscription = DB::table('firm_subscriptions')
                ->where(
                    'gateway_order_id',
                    $request->razorpay_order_id
                )
                ->first();

            DB::beginTransaction();
            if ($subscription) {

                DB::table('firm_subscriptions')
                    ->where('id', $subscription->id)
                    ->update([
                        'payment_status' => 'failed',
                        'updated_at' => now(),
                    ]);
            }

            DB::table('payment_logs')->insert([

                'firm_subscription_id' => $subscription?->id,

                'event_type' => 'payment_failed',

                'payload' => json_encode([
                    'razorpay_order_id' => $request->razorpay_order_id,
                    'razorpay_payment_id' => $request->razorpay_payment_id,
                    'request' => $request->all(),
                ]),

                'created_at' => now(),
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment Failure Error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}


//4632 0200 1656 9341
