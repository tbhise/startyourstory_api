<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\WalletHelper;

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

    /** Returns a 403 response if the student has looking_for = 'creator'. */
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
    | GET /wallet — balance + recharge packs
    |--------------------------------------------------------------------------
    */
    public function getWallet(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            if ($err = $this->creatorGuard($user)) return $err;

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
    | GET /student/apply-status — lightweight check for apply-limit awareness
    |--------------------------------------------------------------------------
    */
    public function getApplyStatus(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            if ($err = $this->creatorGuard($user)) return $err;

            $wallet       = WalletHelper::getOrCreate($user->id);
            $freeUsed     = (int) $wallet->free_applications_used;
            $freeLimit    = (int) $wallet->free_applications_limit;
            $freeRemaining = max(0, $freeLimit - $freeUsed);

            return response()->json([
                'status' => true,
                'data'   => [
                    'free_remaining'   => $freeRemaining,
                    'free_limit'       => $freeLimit,
                    'free_used'        => $freeUsed,
                    'can_apply_free'   => $freeRemaining > 0,
                    'application_fee'  => WalletHelper::APPLICATION_FEE,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('WalletController@getApplyStatus: ' . $e->getMessage());
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
            if ($err = $this->creatorGuard($user)) return $err;

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
            if ($err = $this->creatorGuard($user)) return $err;

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
    | POST /wallet/recharge/manual — submit manual payment proof
    |--------------------------------------------------------------------------
    */
    public function submitManualRecharge(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            if ($err = $this->creatorGuard($user)) return $err;

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

    /*
    |--------------------------------------------------------------------------
    | POST /student/premium-request — submit premium subscription payment proof
    |--------------------------------------------------------------------------
    */
    public function submitPremiumRequest(Request $request)
    {
        try {
            $user = $this->getStudent($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            if ($err = $this->creatorGuard($user)) return $err;

            $validator = Validator::make($request->all(), [
                'plan'             => 'required|string|in:premium-monthly,premium-quarterly,premium-yearly',
                'amount'           => 'required|numeric|min:1',
                'payment_date'     => 'required|date',
                'utr_number'       => 'nullable|string|max:100',
                'reference_number' => 'nullable|string|max:100',
                'screenshot'       => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()]);
            }

            // Block duplicate pending request
            $existing = DB::table('student_premium_requests')
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->first();
            if ($existing) {
                return response()->json([
                    'status'  => false,
                    'message' => 'You already have a pending premium request. Please wait for admin review.',
                ], 422);
            }

            $screenshotUrl = null;
            if ($request->hasFile('screenshot')) {
                $file          = $request->file('screenshot');
                $screenshotUrl = $file->storeAs(
                    'premium_screenshots',
                    time() . '_' . $user->id . '.' . $file->getClientOriginalExtension(),
                    'public'
                );
            }

            $id = DB::table('student_premium_requests')->insertGetId([
                'user_id'          => $user->id,
                'plan'             => $request->plan,
                'amount'           => (float) $request->amount,
                'utr_number'       => $request->utr_number,
                'reference_number' => $request->reference_number,
                'screenshot_url'   => $screenshotUrl,
                'payment_date'     => $request->payment_date,
                'status'           => 'pending',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Premium request submitted. Admin will review within 24 hours.',
                'data'    => ['request_id' => $id],
            ]);
        } catch (\Exception $e) {
            Log::error('WalletController@submitPremiumRequest: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
