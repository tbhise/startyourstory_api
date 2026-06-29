<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Vinkla\Hashids\Facades\Hashids;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use App\Services\Notifications\EmailNotificationService;
use App\Helpers\ReferralHelper;
use App\Helpers\NotificationHelper;
use App\Helpers\AuthHelper;
use App\Services\AdminActivityLogger;

class AdminController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }
            $admin = DB::table('admin_users')
                ->where('email', $request->email)
                ->first();
            if (!$admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }
            if (!Hash::check($request->password, $admin->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials'
                ], 401);
            }
            if (!$admin->is_active) {
                return response()->json([
                    'status' => false,
                    'message' => 'Account is inactive'
                ], 403);
            }
            $token = Str::random(80);
            DB::table('admin_users')
                ->where('id', $admin->id)
                ->update([
                    'api_token' => $token,
                    // Denormalized "Last Login" — set EXPLICITLY on successful login.
                    // (Do NOT rely on updated_at; it drifts on any admin row change.)
                    'last_login_at' => now(),
                    'updated_at' => now(),
                ]);
            return response()->json([
                'status' => true,
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $admin->id,
                        'name' => $admin->name,
                        'email' => $admin->email,
                        'role' => $admin->role,
                    ]
                ]
            ])->cookie(
                'admin_token',
                $token,
                60 * 24 * 30,
                '/',
                null,
                false,
                true
            );
        } catch (\Exception $e) {
            Log::error(
                'Admin Login Error',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error'
            ], 500);
        }
    }
    public function me(Request $request)
    {
        try {
            $token =
                $request->cookie('admin_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $admin = DB::table('admin_users')
                ->where(
                    'api_token',
                    $token
                )
                ->first();
            if (!$admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            return response()->json([
                'status' => true,
                'data' => [
                    'user' => [
                        'id' =>
                        $admin->id,
                        'name' =>
                        $admin->name,
                        'email' =>
                        $admin->email,
                        'role' =>
                        $admin->role,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error'
            ], 500);
        }
    }
    public function logout(Request $request)
    {
        try {
            $token =
                $request->cookie('admin_token');
            DB::table('admin_users')
                ->where(
                    'api_token',
                    $token
                )
                ->update([
                    'api_token' => null
                ]);
            return response()->json([
                'status' => true,
                'message' =>
                'Logout successful'
            ])->cookie(
                'admin_token',
                '',
                -1
            );
        } catch (\Exception $e) {
            return response()->json([
                'status' => false
            ]);
        }
    }
    /**
     *
     *
     *
     *
     *
     */
    public function getAdminSubscriptions(Request $request)
    {
        try {
            $search = trim($request->search ?? '');
            // firm_subscriptions.firm_id is stored under two historical conventions:
            //   - admin-assigned rows (addSubscriptions)      → users.id
            //   - payment/PhonePe rows (PhonePeFirmController) → firm_profiles.id
            // Resolve the firm under BOTH so firm_name/email are never null (the cause
            // of the "Viewing null" bug). Each alias matches at most one row (id is PK,
            // user_id is unique per firm), so no row duplication is introduced.
            $query = DB::table('firm_subscriptions')
                ->leftJoin('firm_profiles as fp_uid', 'firm_subscriptions.firm_id', '=', 'fp_uid.user_id')
                ->leftJoin('firm_profiles as fp_pid', 'firm_subscriptions.firm_id', '=', 'fp_pid.id')
                ->leftJoin('users as u_uid', 'fp_uid.user_id', '=', 'u_uid.id')
                ->leftJoin('users as u_pid', 'fp_pid.user_id', '=', 'u_pid.id')
                ->select(
                    'firm_subscriptions.id',
                    'firm_subscriptions.firm_id',
                    'firm_subscriptions.contact_person',
                    'firm_subscriptions.plan',
                    'firm_subscriptions.status',
                    'firm_subscriptions.starts_at',
                    'firm_subscriptions.expires_at',
                    'firm_subscriptions.created_at',
                    'firm_subscriptions.updated_at',
                    DB::raw('COALESCE(fp_uid.firm_name, fp_pid.firm_name) as firm_name'),
                    DB::raw('COALESCE(u_uid.email, u_pid.email) as firm_email')
                )
                ->orderByDesc('firm_subscriptions.id');
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $like = '%' . $search . '%';
                    $q->where('fp_uid.firm_name', 'LIKE', $like)
                        ->orWhere('fp_pid.firm_name', 'LIKE', $like)
                        ->orWhere('u_uid.email', 'LIKE', $like)
                        ->orWhere('u_pid.email', 'LIKE', $like);
                });
            }
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 20);
            $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

            // Total matching rows (pre-limit) for pagination metadata. Cloning
            // keeps the search WHERE + joins; count() drops the SELECT/ORDER BY.
            $total = (clone $query)->count();
            $subscriptions = $query->forPage($page, $perPage)->get();


            $formatted =
                $subscriptions->map(function ($item) {
                    return [
                        'id' => (string) $item->id,
                        'firm_id' => (string) $item->firm_id,
                        'firm_name' => $item->firm_name,
                        'contact' => $item->contact_person,
                        'firm_email' => $item->firm_email,
                        'plan' => $item->plan,
                        'status' => $item->status,
                        'starts_at' => $item->starts_at ? date('d M Y', strtotime($item->starts_at)) : null,
                        'expires_at' => $item->expires_at ? date('d M Y', strtotime($item->expires_at)) : null,
                        'created_at' => $item->created_at ? date('d M Y h:i A', strtotime($item->created_at)) : null,
                        'updated_at' => $item->updated_at ? date('d M Y h:i A', strtotime($item->updated_at)) : null,
                    ];
                });
            return response()->json([
                'status' => true,
                'message' =>
                'Subscriptions fetched successfully',
                'data' => [
                    'subscriptions' => $formatted,
                    // `total` was previously the firm_profiles count (unused by the
                    // UI); it now reflects the real matching-subscription total so
                    // pagination math is correct. Key name kept for compatibility.
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'last_page' => (int) max(1, ceil($total / $perPage)),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Get Admin Subscriptions Error',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error'
            ]);
        }
    }
    public function addSubscriptions(Request $request)
    {
        try {
            $validator = Validator::make(
                $request->all(),
                [
                    'firm_id' => 'required|exists:firm_profiles,user_id',
                    'plan' => 'required|in:free,premium',
                    'status' => 'required|in:active,expired,cancelled',
                    'starts_at' => 'nullable|date',
                    'expires_at' => 'nullable|date',
                ]
            );
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                ]);
            }
            $firm = DB::table('firm_profiles')
                ->where('user_id', $request->firm_id)
                ->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm not found'
                ]);
            }
            DB::beginTransaction();
            DB::table('firm_subscriptions')
                ->where('firm_id', $request->firm_id)
                ->where('status', 'active')
                ->update([
                    'status' => 'expired',
                    'updated_at' => now(),
                ]);
            $subscriptionId =
                DB::table('firm_subscriptions')
                ->insertGetId([
                    'firm_id' => $request->firm_id,
                    'plan' => $request->plan,
                    'status' => $request->status,
                    'starts_at' => !empty($request->starts_at) ? Carbon::parse($request->starts_at)->format('Y-m-d H:i:s') : now(),
                    'expires_at' => !empty($request->expires_at) ? Carbon::parse($request->expires_at)->format('Y-m-d H:i:s') : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            // Sync is_premium flag on firm_profiles
            $isPremiumPlan = str_contains($request->plan, 'premium') && $request->status === 'active';
            DB::table('firm_profiles')
                ->where('id', $firm->id)
                ->update(['is_premium' => $isPremiumPlan ? 1 : 0, 'updated_at' => now()]);

            // Firm referral: create a pending ₹2,000 payout if this firm was referred.
            if ($isPremiumPlan) {
                ReferralHelper::onFirmPremiumActivated((int) $firm->id);
            }

            DB::commit();
            $subscription =
                DB::table('firm_subscriptions')
                ->where('id', $subscriptionId)
                ->first();

            AdminActivityLogger::log(
                $this->adminFromRequest($request),
                $isPremiumPlan ? AdminActivityLogger::FIRM_PREMIUM_CHANGED : AdminActivityLogger::SUBSCRIPTION_CREATED,
                'firm',
                $request->firm_id,
                'Set subscription for ' . ($firm->firm_name ?? ('firm #' . $request->firm_id)) . " to plan '{$request->plan}' (status {$request->status}).",
                $request
            );

            return response()->json([
                'status' => true,
                'message' => 'Subscription added successfully',
                'data' => [
                    'subscription' => [
                        'id' => (string)$subscription->id,
                        'firm_id' => (string)$subscription->firm_id,
                        'plan' => $subscription->plan,
                        'status' => $subscription->status,
                        'starts_at' => $subscription->starts_at,
                        'expires_at' => $subscription->expires_at,
                        'created_at' => $subscription->created_at,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(
                'Add Subscription Error',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error'
            ]);
        }
    }
    public function submitPremiumRequest(Request $request)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | Authenticate User
        |--------------------------------------------------------------------------
        */
            DB::beginTransaction();
            // User-facing endpoint (firm submits a premium request). Resolves the
            // logged-in user via user_sessions; this route is outside ApiAuthMiddleware.
            $user = AuthHelper::resolveUser($request);
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            /*
        |--------------------------------------------------------------------------
        | Recruiter Only
        |--------------------------------------------------------------------------
        */
            if ($user->role !== 'firm') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only firms can submit premium requests'
                ], 403);
            }
            /*
        |--------------------------------------------------------------------------
        | Validate Inputs
        |--------------------------------------------------------------------------
        */
            $validator = Validator::make($request->all(), [
                'firm_id' =>
                'required|integer',
                'firm_name' =>
                'required|string|max:255',
                'contact_person' =>
                'required|string|max:255',
                'transaction_id' =>
                'required|string|max:255',
                'payment_date' =>
                'required|date',
                'plan' =>
                'required|string',
                'screenshot_url' =>
                'required|string',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }
            /*
        |--------------------------------------------------------------------------
        | Upload Screenshot
        |--------------------------------------------------------------------------
        */
            $screenshotPath = null;
            if ($request->screenshot_url) {
                $image = $request->screenshot_url;
                if (
                    preg_match(
                        '/^data:image\/(\w+);base64,/',
                        $image,
                        $type
                    )
                ) {
                    $image = substr(
                        $image,
                        strpos($image, ',') + 1
                    );
                    $type = strtolower($type[1]);
                    $image = base64_decode($image);
                    if ($image === false) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Invalid image format'
                        ], 422);
                    }
                    $fileName =
                        'premium_' .
                        time() .
                        '.' .
                        $type;
                    Storage::disk('public')->put(
                        'premium-payments/' . $fileName,
                        $image
                    );
                    $screenshotPath =
                        'premium-payments/' . $fileName;
                }
            }
            /*
        |--------------------------------------------------------------------------
        | Insert Request
        |--------------------------------------------------------------------------
        */
            $id = DB::table('premium_requests')->insertGetId([
                'firm_id' =>
                $request->firm_id,
                'firm_name' =>
                $request->firm_name,
                'contact_person' =>
                $request->contact_person,
                'transaction_id' =>
                $request->transaction_id,
                'payment_date' =>
                $request->payment_date,
                'plan' =>
                $request->plan,
                'amount' =>
                $request->amount,
                'screenshot_url' =>
                $screenshotPath,
                'status' => 'pending',
                'created_at' =>
                now(),
                'updated_at' =>
                now(),
            ]);
            /*
        |--------------------------------------------------------------------------
        | Get Created Request
        |--------------------------------------------------------------------------
        */
            $premiumRequest = DB::table('premium_requests')
                ->where('id', $id)
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
            DB::commit();

            // Admin notification feed — premium purchase awaiting verification (non-throwing)
            \App\Services\Notifications\AdminNotificationService::premiumRequest(
                $request->firm_name ?? 'A firm',
                (string) $request->plan,
                (float) $request->amount,
                $id,
                $request->firm_id !== null ? (int) $request->firm_id : null
            );

            return response()->json([
                'status' => true,
                'message' =>
                'Premium request submitted successfully',
                'data' => [
                    'request' =>
                    $premiumRequest
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(
                'Submit Premium Request Error',
                [
                    'message' =>
                    $e->getMessage(),
                    'line' =>
                    $e->getLine(),
                    'file' =>
                    $e->getFile(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error'
            ], 500);
        }
    }
    public function getPremiumRequests(Request $request)
    {
        try {
            $token = $request->cookie('admin_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $user = DB::table('admin_users')
                ->where('api_token', $token)
                ->where('is_active', true)
                ->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid token'
                ], 401);
            }
            // if ($user->role !== 'admin') {
            //     return response()->json([
            //         'status' => false,
            //         'message' => 'Only admin can access premium requests'
            //     ], 403);
            // }
            $page = max(1, (int) $request->input('page', 1));
            $perPage = (int) $request->input('per_page', 20);
            $perPage = ($perPage > 0 && $perPage <= 100) ? $perPage : 20;

            // Only pending requests are actionable on the admin screen. Filter
            // server-side instead of returning every historical (approved/rejected)
            // request and discarding them on the client.
            $base = DB::table('premium_requests as pr')
                ->leftJoin('firm_profiles as fp', 'fp.id', '=', 'pr.firm_id')
                ->where('pr.status', 'pending');

            $total = (clone $base)->count();

            $requests = $base
                ->select(
                    'pr.id',
                    'pr.firm_id',
                    'pr.contact_person',
                    'pr.firm_name',
                    'pr.plan as plan_label',
                    'pr.transaction_id',
                    'pr.amount',
                    'pr.payment_date',
                    DB::raw("CONCAT('" . url('/storage') . "/', pr.screenshot_url) as screenshot_url"),
                    'pr.status',
                    'pr.remarks',
                    'pr.created_at',
                    'fp.logo_path as logo',
                    'fp.city',
                )
                ->orderByDesc('pr.created_at')
                ->forPage($page, $perPage)
                ->get()
                ->map(function ($item) {
                    $item->id =
                        Hashids::encode(
                            $item->id
                        );
                    return $item;
                });
            return response()->json([
                'status' => true,
                'message' =>
                'Premium requests fetched successfully',
                'data' => [
                    'requests' => $requests,
                    'total' => $total,
                    'page' => $page,
                    'per_page' => $perPage,
                    'last_page' => (int) max(1, ceil($total / $perPage)),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Get Premium Requests Error',
                [
                    'message' =>
                    $e->getMessage(),
                    'line' =>
                    $e->getLine(),
                    'file' =>
                    $e->getFile(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error'
            ], 500);
        }
    }
    /**
     * List student premium purchase requests (admin review).
     * Mirrors getPremiumRequests but reads student_premium_requests + users.
     */
    public function getStudentPremiumRequests(Request $request)
    {
        try {
            $token = $request->cookie('admin_token');
            if (!$token) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }
            $admin = DB::table('admin_users')
                ->where('api_token', $token)
                ->where('is_active', true)
                ->first();
            if (!$admin) {
                return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
            }

            $requests = DB::table('student_premium_requests as spr')
                ->leftJoin('users as u', 'u.id', '=', 'spr.user_id')
                ->select(
                    'spr.id',
                    'spr.user_id',
                    'u.name as student_name',
                    'u.email',
                    'spr.plan',
                    'spr.amount',
                    'spr.utr_number',
                    'spr.reference_number',
                    'spr.payment_date',
                    DB::raw("CONCAT('" . url('/storage') . "/', spr.screenshot_url) as screenshot_url"),
                    'spr.status',
                    'spr.admin_remarks',
                    'spr.created_at',
                )
                ->orderByDesc('spr.created_at')
                ->get()
                ->map(function ($item) {
                    $item->id = Hashids::encode($item->id);
                    return $item;
                });

            return response()->json([
                'status'  => true,
                'message' => 'Student premium requests fetched successfully',
                'data'    => ['requests' => $requests],
            ]);
        } catch (\Exception $e) {
            Log::error('Get Student Premium Requests Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Unexpected server error'], 500);
        }
    }

    /**
     * Approve a student premium request → activate (upsert) the student's
     * subscription so AuthController reports them as premium.
     */
    public function approveStudentPremiumRequest(Request $request, $encryptedId = null)
    {
        DB::beginTransaction();
        try {
            $token = $request->cookie('admin_token');
            if (!$token) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }
            $admin = DB::table('admin_users')->where('api_token', $token)->first();
            if (!$admin) {
                return response()->json(['status' => false, 'message' => 'Invalid admin token'], 401);
            }
            try {
                $id = Hashids::decode($encryptedId)[0] ?? null;
            } catch (\Exception $e) {
                return response()->json(['status' => false, 'message' => 'Invalid request id'], 422);
            }

            $premiumRequest = DB::table('student_premium_requests')->where('id', $id)->first();
            if (!$premiumRequest) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Premium request not found'], 404);
            }
            if ($premiumRequest->status === 'approved') {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Request already approved'], 422);
            }

            $startsAt  = now();
            $expiresAt = match ($premiumRequest->plan) {
                'premium-yearly'    => now()->addYear(),
                'premium-quarterly' => now()->addMonths(3),
                default             => now()->addMonth(),
            };

            // One subscription row per student — update the existing row if present,
            // otherwise insert. Mirrors the firm activation upsert.
            $existing = DB::table('student_subscriptions')->where('user_id', $premiumRequest->user_id)->first();
            if ($existing) {
                DB::table('student_subscriptions')->where('id', $existing->id)->update([
                    'plan'       => $premiumRequest->plan,
                    'status'     => 'active',
                    'starts_at'  => $startsAt,
                    'expires_at' => $expiresAt,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('student_subscriptions')->insert([
                    'user_id'    => $premiumRequest->user_id,
                    'plan'       => $premiumRequest->plan,
                    'status'     => 'active',
                    'starts_at'  => $startsAt,
                    'expires_at' => $expiresAt,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('student_premium_requests')->where('id', $premiumRequest->id)->update([
                'status'        => 'approved',
                'admin_remarks' => $request->remarks,
                'reviewed_by'   => $admin->id,
                'reviewed_at'   => now(),
                'updated_at'    => now(),
            ]);

            $updatedRequest = DB::table('student_premium_requests')->where('id', $premiumRequest->id)->first();
            $updatedRequest->id = Hashids::encode($updatedRequest->id);

            DB::commit();

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::SUBSCRIPTION_APPROVED,
                'student_premium_request',
                $premiumRequest->id,
                'Approved student premium subscription (' . $premiumRequest->plan . ') for user #' . $premiumRequest->user_id . '.',
                $request
            );

            return response()->json([
                'status'  => true,
                'message' => 'Student premium request approved',
                'data'    => ['request' => $updatedRequest],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Approve Student Premium Request Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Unexpected server error'], 500);
        }
    }

    /**
     * Reject a student premium request (no subscription change).
     */
    public function rejectStudentPremiumRequest(Request $request, $encryptedId = null)
    {
        DB::beginTransaction();
        try {
            $token = $request->cookie('admin_token');
            if (!$token) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }
            $admin = DB::table('admin_users')->where('api_token', $token)->first();
            if (!$admin) {
                return response()->json(['status' => false, 'message' => 'Invalid admin token'], 401);
            }
            try {
                $id = Hashids::decode($encryptedId)[0] ?? null;
            } catch (\Exception $e) {
                return response()->json(['status' => false, 'message' => 'Invalid request id'], 422);
            }

            $premiumRequest = DB::table('student_premium_requests')->where('id', $id)->first();
            if (!$premiumRequest) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Premium request not found'], 404);
            }
            if ($premiumRequest->status === 'rejected') {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Request already rejected'], 422);
            }

            DB::table('student_premium_requests')->where('id', $premiumRequest->id)->update([
                'status'        => 'rejected',
                'admin_remarks' => $request->remarks,
                'reviewed_by'   => $admin->id,
                'reviewed_at'   => now(),
                'updated_at'    => now(),
            ]);

            $updatedRequest = DB::table('student_premium_requests')->where('id', $premiumRequest->id)->first();
            $updatedRequest->id = Hashids::encode($updatedRequest->id);

            DB::commit();

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::SUBSCRIPTION_REJECTED,
                'student_premium_request',
                $premiumRequest->id,
                'Rejected student premium subscription request for user #' . $premiumRequest->user_id . '.',
                $request
            );

            return response()->json([
                'status'  => true,
                'message' => 'Student premium request rejected',
                'data'    => ['request' => $updatedRequest],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Reject Student Premium Request Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Unexpected server error'], 500);
        }
    }

    public function approvePremiumRequest(
        Request $request,
        $encryptedId = null
    ) {
        DB::beginTransaction();
        try {
            $token = $request->cookie('admin_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            $admin = DB::table('admin_users')
                ->where('api_token', $token)
                ->first();
            if (!$admin) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid admin token'
                ], 401);
            }
            try {
                $id = Hashids::decode($encryptedId)[0] ?? null;
            } catch (\Exception $e) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid request id'
                ], 422);
            }
            $premiumRequest =
                DB::table('premium_requests')
                ->where('id', $id)
                ->first();
            if (!$premiumRequest) {
                return response()->json([
                    'status' => false,
                    'message' => 'Premium request not found'
                ], 404);
            }
            if ($premiumRequest->status === 'approved') {
                return response()->json([
                    'status' => false,
                    'message' => 'Request already approved'
                ], 422);
            }
            $startsAt = now();
            $expiresAt =
                $premiumRequest->plan ===
                'premium-yearly' ? now()->addYear() : now()->addMonth();

            // Resolve the firm's REAL firm_profiles.id. premium_requests.firm_id
            // has historically been stored as the USER id (the firm payment page
            // sends user.id), whereas firm_subscriptions.firm_id and the
            // firm_profiles.is_premium flag are keyed on firm_profiles.id. Accept
            // either form: match by user_id first, then fall back to id. Without
            // this, activation writes target the wrong/non-existent row and the
            // firm never actually becomes premium.
            $firmProfile = DB::table('firm_profiles')
                ->where('user_id', $premiumRequest->firm_id)
                ->first()
                ?? DB::table('firm_profiles')
                ->where('id', $premiumRequest->firm_id)
                ->first();
            if (!$firmProfile) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Firm profile not found for this request'
                ], 404);
            }
            $firmProfileId = $firmProfile->id;

            $existingSubscription =
                DB::table('firm_subscriptions')
                ->where('firm_id', $firmProfileId)
                ->first();
            if ($existingSubscription) {
                DB::table('firm_subscriptions')
                    ->where('id', $existingSubscription->id)
                    ->update([
                        'contact_person' => $premiumRequest->contact_person,
                        'plan' => $premiumRequest->plan === 'premium-yearly' ? 'premium' : $premiumRequest->plan,
                        'amount' => $premiumRequest->amount,
                        'currency' => 'INR',
                        'payment_gateway' => 'manual',
                        'payment_method' => 'manual',
                        'transaction_id' => $premiumRequest->transaction_id,
                        // A manually-approved payment IS paid — mark it so, otherwise
                        // billing/reporting (which filter on payment_status) hide it.
                        'payment_status' => 'paid',
                        'payment_date' => $premiumRequest->payment_date,
                        'screenshot_url' => $premiumRequest->screenshot_url,
                        'remarks' => $request->remarks,
                        'status' => 'active',
                        'starts_at' => $startsAt,
                        'expires_at' => $expiresAt,
                        'updated_at' => now(),
                    ]);
            } else {
                DB::table('firm_subscriptions')
                    ->insert([
                        'firm_id' => $firmProfileId,
                        'contact_person' => $premiumRequest->contact_person,
                        'plan' => $premiumRequest->plan === 'premium-yearly' ? 'premium' : $premiumRequest->plan,
                        'amount' => $premiumRequest->amount,
                        'currency' => 'INR',
                        'payment_gateway' => 'manual',
                        'payment_method' => 'manual',
                        'transaction_id' => $premiumRequest->transaction_id,
                        'payment_status' => 'paid',
                        'payment_date' => $premiumRequest->payment_date,
                        'screenshot_url' => $premiumRequest->screenshot_url,
                        'remarks' => $request->remarks,
                        'status' => 'active',
                        'starts_at' => $startsAt,
                        'expires_at' => $expiresAt,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }
            DB::table('premium_requests')
                ->where('id', $premiumRequest->id)
                ->update([
                    'status' => 'approved',
                    'remarks' => $request->remarks,
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                    'updated_at' => now(),
                ]);

            // Sync is_premium flag on firm_profiles
            DB::table('firm_profiles')
                ->where('id', $firmProfileId)
                ->update(['is_premium' => 1, 'updated_at' => now()]);

            // Firm referral: create a pending ₹2,000 payout if this firm was referred.
            ReferralHelper::onFirmPremiumActivated((int) $firmProfileId);
            $updatedRequest =
                DB::table('premium_requests')
                ->select(
                    'id',
                    'firm_id',
                    'contact_person',
                    'firm_name',
                    'plan',
                    'transaction_id',
                    'payment_date',
                    DB::raw("CONCAT('" . url('/storage') . "/', screenshot_url) as screenshot_url"),
                    'remarks',
                    'status',
                    'approved_by',
                    'approved_at'
                )
                ->where('id', $premiumRequest->id)
                ->first();
            DB::commit();

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::SUBSCRIPTION_APPROVED,
                'premium_request',
                $premiumRequest->id,
                'Approved premium subscription payment for ' . ($premiumRequest->firm_name ?? ('firm #' . $premiumRequest->firm_id)) . ' (' . $premiumRequest->plan . ').',
                $request
            );

            return response()->json([
                'status' => true,
                'message' => 'Premium request approved successfully',
                'data' => [
                    'request' => $updatedRequest
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(
                'Approve Premium Request Error',
                [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' => 'Unexpected server error'
            ], 500);
        }
    }
    public function getPendingFirms(Request $request)
    {
        try {
            $token = $request->cookie('admin_token');
            if (!$token) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            $admin = DB::table('admin_users')->where('api_token', $token)->where('is_active', true)->first();
            if (!$admin) return response()->json(['status' => false, 'message' => 'Invalid token'], 401);

            $status = $request->input('status', 'pending');

            $firms = DB::table('firm_profiles as fp')
                ->join('users as u', 'u.id', '=', 'fp.user_id')
                ->where('fp.verification_status', $status)
                ->select(
                    'fp.user_id as id',
                    'fp.firm_name',
                    'fp.frn',
                    'fp.hr_name',
                    'fp.firm_type',
                    'fp.city',
                    'fp.address',
                    'fp.about',
                    'fp.linkedin_url',
                    'fp.website_url',
                    'fp.verification_status',
                    'fp.rejection_reason',
                    'fp.created_at',
                    'u.email',
                    'u.mobile',
                    'u.email_verified_at',
                    'u.profile_completed',
                    'u.last_login_at',
                    DB::raw('IF(u.email_verified_at IS NOT NULL, 1, 0) as is_verified'),
                    DB::raw("CASE WHEN fp.logo_path IS NOT NULL THEN CONCAT('" . url('/storage') . "/', fp.logo_path) ELSE NULL END as logo_url")
                )
                ->orderByDesc('fp.created_at')
                ->get();

            return response()->json([
                'status' => true,
                'data' => ['firms' => $firms, 'total' => $firms->count()]
            ]);
        } catch (\Exception $e) {
            Log::error('getPendingFirms Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * GET /admin/firms/{id}
     * Full firm detail for the admin "View" modal — joins the users row with the
     * firm_profiles row. Read-only; mirrors getStudent's shape.
     */
    public function getFirm(Request $request, $id)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $firm = DB::table('users as u')
                ->leftJoin('firm_profiles as fp', 'fp.user_id', '=', 'u.id')
                ->where('u.id', $id)
                ->where('u.role', 'firm')
                ->select(
                    'u.id',
                    'u.name',
                    'u.email',
                    'u.mobile',
                    'u.profile_completed',
                    'u.email_verified_at',
                    'u.created_at',
                    'u.is_deleted',
                    'u.deletion_requested_at',
                    'u.deletion_reason',
                    'fp.firm_name',
                    'fp.frn',
                    'fp.hr_name',
                    'fp.firm_type',
                    'fp.city',
                    'fp.address',
                    'fp.about',
                    'fp.establishment_year',
                    'fp.employees_count',
                    'fp.partners_count',
                    'fp.articles_count',
                    'fp.exposure_type',
                    'fp.services_offered',
                    'fp.industries_served',
                    'fp.work_modes',
                    'fp.training_details',
                    'fp.stipend_details',
                    'fp.linkedin_url',
                    'fp.website_url',
                    'fp.instagram_url',
                    'fp.facebook_url',
                    'fp.other_links',
                    'fp.is_premium',
                    'fp.is_branch',
                    'fp.parent_frn',
                    'fp.verification_status',
                    'fp.rejection_reason',
                    'fp.logo_path'
                )
                ->first();

            if (!$firm) {
                return response()->json(['status' => false, 'message' => 'Firm not found'], 404);
            }

            $data = (array) $firm;
            $data['logo_url']     = $firm->logo_path ? url('/storage/' . ltrim($firm->logo_path, '/')) : null;
            $data['is_verified']  = !empty($firm->email_verified_at);
            $data['plan']         = !empty($firm->is_premium) ? 'premium' : 'free';

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('getFirm Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * DELETE /admin/firms/{id}
     *
     * Admin-initiated deletion of a firm. Like deleteStudent this is a SOFT delete
     * by design — a firm touches many tables (firm_profiles, jobs, applications,
     * subscriptions, conversations, branch links …), several of them firm-facing
     * audit / financial records, so a hard delete would orphan or destroy history.
     * Instead we flag the account deleted and hide it everywhere `is_deleted = false`
     * is already filtered (admin listing, auth resolution).
     *
     * A mandatory `reason` is required and stored on `users.deletion_reason`.
     *
     * Effects (one transaction):
     *  - users.is_deleted = 1, deletion timestamps stamped, deletion_reason saved,
     *    api_token cleared.
     *  - active login sessions invalidated (force logout).
     * No related rows are deleted, so no orphans are created.
     */
    public function deleteFirm(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $admin = $this->adminFromRequest($request);
            if (!$admin) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
            ], [
                'reason.required' => 'A reason is required to delete this firm.',
            ]);
            if ($validator->fails()) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }
            $reason = trim($request->input('reason'));

            $firm = DB::table('users')
                ->where('id', $id)
                ->where('role', 'firm')
                ->first();

            if (!$firm) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Firm not found'], 404);
            }

            if (!empty($firm->is_deleted)) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Firm is already deleted'], 422);
            }

            $now = now();

            DB::table('users')->where('id', $id)->update([
                'is_deleted'            => 1,
                'deletion_requested_at' => $now,
                'scheduled_deletion_at' => $now,
                'deletion_reason'       => $reason,
                'updated_at'            => $now,
            ]);

            // Force logout — drop any active sessions for this user.
            DB::table('user_sessions')->where('user_id', $id)->delete();

            DB::commit();

            $firmName = DB::table('firm_profiles')->where('user_id', $id)->value('firm_name');

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::FIRM_DELETED,
                'firm',
                $id,
                'Deleted firm account for ' . ($firmName ?: ($firm->name ?? ('firm #' . $id)))
                    . ' (' . ($firm->email ?? '') . '). Reason: ' . $reason,
                $request
            );

            return response()->json(['status' => true, 'message' => 'Firm deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('deleteFirm Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function approveFirm(Request $request, $firmId)
    {
        DB::beginTransaction();
        try {
            $token = $request->cookie('admin_token');
            if (!$token) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            $admin = DB::table('admin_users')->where('api_token', $token)->where('is_active', true)->first();
            if (!$admin) return response()->json(['status' => false, 'message' => 'Invalid token'], 401);

            $firm = DB::table('firm_profiles')->where('user_id', $firmId)->first();
            if (!$firm) return response()->json(['status' => false, 'message' => 'Firm not found'], 404);

            if ($firm->verification_status === 'approved') {
                return response()->json(['status' => false, 'message' => 'Firm already approved'], 422);
            }

            // Guard: a firm cannot be approved until it has completed its profile.
            // profile_completed is maintained on the users row (see FirmController).
            $firmUser = DB::table('users')->where('id', $firmId)->first();
            if (!$firmUser || empty($firmUser->profile_completed)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Firm profile must be completed before approval.',
                ], 422);
            }

            DB::table('firm_profiles')
                ->where('user_id', $firmId)
                ->update([
                    'verification_status' => 'approved',
                    'rejection_reason' => null,
                    'updated_at' => now(),
                ]);

            $user = DB::table('users')->where('id', $firmId)->first();
            if ($user) {
                app(EmailNotificationService::class)->sendFirmApproved(
                    $user->email,
                    $firm->firm_name ?? $user->name
                );
            }

            DB::commit();

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::FIRM_APPROVED,
                'firm',
                $firmId,
                'Approved firm registration for ' . ($firm->firm_name ?? ('firm #' . $firmId)) . '.',
                $request
            );

            return response()->json(['status' => true, 'message' => 'Firm approved successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('approveFirm Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function rejectFirm(Request $request, $firmId)
    {
        DB::beginTransaction();
        try {
            $token = $request->cookie('admin_token');
            if (!$token) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            $admin = DB::table('admin_users')->where('api_token', $token)->where('is_active', true)->first();
            if (!$admin) return response()->json(['status' => false, 'message' => 'Invalid token'], 401);

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:1000',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $firm = DB::table('firm_profiles')->where('user_id', $firmId)->first();
            if (!$firm) return response()->json(['status' => false, 'message' => 'Firm not found'], 404);

            DB::table('firm_profiles')
                ->where('user_id', $firmId)
                ->update([
                    'verification_status' => 'rejected',
                    'rejection_reason' => $request->reason,
                    'updated_at' => now(),
                ]);

            $user = DB::table('users')->where('id', $firmId)->first();
            if ($user) {
                app(EmailNotificationService::class)->sendFirmRejected(
                    $user->email,
                    $firm->firm_name ?? $user->name,
                    $request->reason ?? ''
                );
            }

            DB::commit();

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::FIRM_REJECTED,
                'firm',
                $firmId,
                'Rejected firm registration for ' . ($firm->firm_name ?? ('firm #' . $firmId)) . '. Reason: ' . $request->reason,
                $request
            );

            return response()->json(['status' => true, 'message' => 'Firm rejected successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('rejectFirm Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Firm & Student directory listings
    // ─────────────────────────────────────────────────────────────────────────

    public function getFirms(Request $request)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $query = DB::table('firm_profiles as fp')
                ->join('users as u', 'u.id', '=', 'fp.user_id')
                ->where('u.role', 'firm')
                ->where('u.is_deleted', false);

            if ($request->filled('search')) {
                $s = '%' . trim($request->search) . '%';
                $query->where(function ($q) use ($s) {
                    $q->where('fp.firm_name', 'like', $s)
                        ->orWhere('u.email', 'like', $s)
                        ->orWhere('u.mobile', 'like', $s)
                        ->orWhere('fp.hr_name', 'like', $s)
                        ->orWhere('fp.frn', 'like', $s);
                });
            }

            if ($request->filled('city')) {
                $query->where('fp.city', $request->city);
            }

            // Email verification filter: all | verified | not_verified
            $emailVerified = $request->input('email_verified');
            if ($emailVerified === 'verified') {
                $query->whereNotNull('u.email_verified_at');
            } elseif ($emailVerified === 'not_verified') {
                $query->whereNull('u.email_verified_at');
            }

            // Profile completion filter: all | completed | incomplete
            // Uses the platform's existing users.profile_completed flag.
            $profileCompletion = $request->input('profile_completion');
            if ($profileCompletion === 'completed') {
                $query->where('u.profile_completed', 1);
            } elseif ($profileCompletion === 'incomplete') {
                $query->where(function ($q) {
                    $q->where('u.profile_completed', 0)->orWhereNull('u.profile_completed');
                });
            }

            // Login-activity filter (denormalized users.last_login_at — same buckets
            // as the Students listing): active / warm / inactive / dormant / never.
            $this->applyActivityFilter($query, 'u.last_login_at', $request->input('activity'));

            // Sort: default newest-registered first; admin can switch to login recency.
            $this->applyLastLoginSort($query, 'u.last_login_at', $request->input('sort'), 'fp.created_at');

            // Server-side pagination. Default 25, clamped to [1, 100]. The page
            // number is resolved from the request's `page` query param by Laravel's
            // paginator. Filters/search/sorting above are unchanged.
            $pageSize = min(max((int) $request->input('per_page', 25), 1), 100);

            $firms = $query
                ->select(
                    'fp.user_id as id',
                    'fp.firm_name',
                    'fp.firm_type',
                    'fp.frn',
                    'fp.hr_name',
                    'fp.city',
                    'fp.verification_status',
                    'fp.created_at',
                    'u.last_login_at',
                    'u.email',
                    'u.mobile',
                    'u.profile_completed',
                    DB::raw('IF(u.email_verified_at IS NOT NULL, 1, 0) as is_verified'),
                    DB::raw("CASE WHEN fp.is_premium = 1 THEN 'premium' ELSE 'free' END as plan")
                )
                ->paginate($pageSize);

            return response()->json([
                'status' => true,
                'data' => [
                    'firms'    => $firms->items(),
                    'total'    => $firms->total(),
                    'page'     => $firms->currentPage(),
                    'per_page' => $firms->perPage(),
                    'has_more' => $firms->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('getFirms Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getStudents(Request $request)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $pageSize = min(max((int) $request->input('page_size', 25), 1), 100);

            $query = DB::table('users as u')
                ->leftJoin('student_profiles as sp', 'sp.user_id', '=', 'u.id')
                ->where('u.role', 'student');

            // Account status filter: active (default) | deleted | all
            $deletionStatus = $request->input('deletion_status', 'active');
            if ($deletionStatus === 'deleted') {
                $query->where('u.is_deleted', true);
            } elseif ($deletionStatus === 'all') {
                // no is_deleted constraint
            } else {
                $query->where('u.is_deleted', false);
            }

            if ($request->filled('search')) {
                $s = '%' . trim($request->search) . '%';
                $query->where(function ($q) use ($s) {
                    $q->where('u.name', 'like', $s)
                        ->orWhere('u.email', 'like', $s)
                        ->orWhere('u.mobile', 'like', $s);
                });
            }

            if ($request->filled('city')) {
                $query->where('sp.city', $request->city);
            }

            // Email verification filter: all | verified | not_verified
            $emailVerified = $request->input('email_verified');
            if ($emailVerified === 'verified') {
                $query->whereNotNull('u.email_verified_at');
            } elseif ($emailVerified === 'not_verified') {
                $query->whereNull('u.email_verified_at');
            }

            // Profile completion filter: all | completed | incomplete
            // Uses the platform's existing users.profile_completed flag.
            $profileCompletion = $request->input('profile_completion');
            if ($profileCompletion === 'completed') {
                $query->where('u.profile_completed', 1);
            } elseif ($profileCompletion === 'incomplete') {
                $query->where(function ($q) {
                    $q->where('u.profile_completed', 0)->orWhereNull('u.profile_completed');
                });
            }

            // Login-activity filter (denormalized users.last_login_at):
            //   active   → logged in within the last 3 days
            //   warm     → 4–15 days ago
            //   inactive → 16–45 days ago
            //   dormant  → more than 45 days ago
            //   never    → never logged in (NULL)
            $this->applyActivityFilter($query, 'u.last_login_at', $request->input('activity'));

            // Sort: default newest-registered first; admin can switch to login recency.
            $this->applyLastLoginSort($query, 'u.last_login_at', $request->input('sort'), 'u.created_at');

            $students = $query
                ->select(
                    'u.id',
                    'u.name',
                    'u.email',
                    'u.mobile',
                    'u.profile_completed',
                    'u.created_at',
                    'u.last_login_at',
                    'u.is_deleted',
                    'u.deletion_requested_at',
                    'u.scheduled_deletion_at',
                    'sp.looking_for',
                    'sp.city',
                    DB::raw('IF(u.email_verified_at IS NOT NULL, 1, 0) as is_verified')
                )
                ->paginate($pageSize);

            return response()->json([
                'status' => true,
                'data' => [
                    'students' => $students->items(),
                    'current_page' => $students->currentPage(),
                    'last_page' => $students->lastPage(),
                    'total' => $students->total(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('getStudents Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Aggregate counts for the admin Students page stat cards.
     * Single grouped query (no N+1): total active students, email-verified,
     * and profile-completed. "Active" mirrors the listing default (not deleted).
     */
    public function getStudentStats(Request $request)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $row = DB::table('users')
                ->where('role', 'student')
                ->where('is_deleted', false)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified')
                ->selectRaw('SUM(CASE WHEN profile_completed = 1 THEN 1 ELSE 0 END) as profile_completed')
                ->first();

            return response()->json([
                'status' => true,
                'data' => [
                    'total' => (int) ($row->total ?? 0),
                    'verified' => (int) ($row->verified ?? 0),
                    'profile_completed' => (int) ($row->profile_completed ?? 0),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('getStudentStats Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Aggregate counts for the admin Firms page stat cards.
     * Single grouped query (no N+1): total active firms, email-verified,
     * and profile-completed. Mirrors the firm listing scope (role=firm, not deleted).
     */
    public function getFirmStats(Request $request)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $row = DB::table('users')
                ->where('role', 'firm')
                ->where('is_deleted', false)
                ->selectRaw('COUNT(*) as total')
                ->selectRaw('SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified')
                ->selectRaw('SUM(CASE WHEN profile_completed = 1 THEN 1 ELSE 0 END) as profile_completed')
                ->first();

            return response()->json([
                'status' => true,
                'data' => [
                    'total' => (int) ($row->total ?? 0),
                    'verified' => (int) ($row->verified ?? 0),
                    'profile_completed' => (int) ($row->profile_completed ?? 0),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('getFirmStats Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getStudent(Request $request, $id)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $student = DB::table('users as u')
                ->leftJoin('student_profiles as sp', 'sp.user_id', '=', 'u.id')
                ->where('u.id', $id)
                ->where('u.role', 'student')
                ->select(
                    'u.id',
                    'u.name',
                    'u.email',
                    'u.mobile',
                    'u.profile_image',
                    'u.profile_completed',
                    'u.email_verified_at',
                    'u.created_at',
                    'u.is_deleted',
                    'u.deletion_requested_at',
                    'u.scheduled_deletion_at',
                    'u.deletion_reason',
                    'sp.looking_for',
                    'sp.ca_status',
                    'sp.address',
                    'sp.city',
                    'sp.gender',
                    'sp.registration_type',
                    'sp.passing_month',
                    'sp.core_department',
                    'sp.exposure_type',
                    'sp.preferred_location',
                    'sp.experience_years',
                    'sp.current_firm_name',
                    'sp.industry_worked_in',
                    'sp.experience_department',
                    'sp.why_should_hire_you',
                    'sp.current_ctc',
                    'sp.expected_ctc',
                    'sp.resume_path',
                    'sp.marksheet_path',
                    'sp.linkedin_url',
                    'sp.portfolio_url',
                    'sp.instagram_url',
                    'sp.website_url',
                    'sp.qualification',
                    'sp.availability_status',
                    'sp.is_creator'
                )
                ->first();

            if (!$student) {
                return response()->json(['status' => false, 'message' => 'Student not found'], 404);
            }

            $data = (array) $student;
            $data['profile_image'] = $student->profile_image ? asset('storage/' . $student->profile_image) : null;
            $data['is_verified']   = !empty($student->email_verified_at);
            $data['exposure_type']      = $data['exposure_type']      ? (json_decode($data['exposure_type'])      ?? []) : [];
            $data['preferred_location'] = $data['preferred_location'] ? (json_decode($data['preferred_location']) ?? []) : [];

            return response()->json(['status' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('getStudent Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * DELETE /admin/students/{id}
     *
     * Admin-initiated deletion of a student. This is a SOFT delete by design: a
     * student touches many tables (student_profiles, applications, wallet/coin
     * ledgers, referrals, creator engagements, messaging …) and several of those
     * are financial / firm-facing audit records. Hard-deleting would either
     * orphan or destroy that history, so instead we flag the account deleted and
     * hide it everywhere it is already filtered by `is_deleted = false`
     * (admin listing default, auth resolution, etc.).
     *
     * Effects (all inside one transaction):
     *  - users.is_deleted = 1, deletion timestamps stamped.
     *  - active login sessions invalidated (so the student is logged out).
     * No related rows are deleted, so no orphans are created.
     */
    public function deleteStudent(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $admin = $this->adminFromRequest($request);
            if (!$admin) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'required|string|max:500',
            ], [
                'reason.required' => 'A reason is required to delete this student.',
            ]);
            if ($validator->fails()) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }
            $reason = trim($request->input('reason'));

            $student = DB::table('users')
                ->where('id', $id)
                ->where('role', 'student')
                ->first();

            if (!$student) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Student not found'], 404);
            }

            if (!empty($student->is_deleted)) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Student is already deleted'], 422);
            }

            $now = now();

            DB::table('users')->where('id', $id)->update([
                'is_deleted'            => 1,
                'deletion_requested_at' => $now,
                'scheduled_deletion_at' => $now,
                'deletion_reason'       => $reason,
                'updated_at'            => $now,
            ]);

            // Force logout — drop any active sessions for this user.
            DB::table('user_sessions')->where('user_id', $id)->delete();

            DB::commit();

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::STUDENT_DELETED,
                'student',
                $id,
                'Deleted student account for ' . ($student->name ?? ('student #' . $id))
                    . ' (' . ($student->email ?? '') . '). Reason: ' . $reason,
                $request
            );

            return response()->json(['status' => true, 'message' => 'Student deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('deleteStudent Error', ['message' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function rejectPremiumRequest(
        Request $request,
        $encId = null
    ) {
        DB::beginTransaction();
        try {
            /*
        |--------------------------------------------------------------------------
        | Authenticate Admin
        |--------------------------------------------------------------------------
        */
            $token =
                $request->cookie(
                    'admin_token'
                );
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    'Unauthorized'
                ], 401);
            }
            $admin = DB::table('admin_users')
                ->where(
                    'api_token',
                    $token
                )
                ->first();
            if (!$admin) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    'Invalid admin token'
                ], 401);
            }
            /*
        |--------------------------------------------------------------------------
        | Decode ID
        |--------------------------------------------------------------------------
        */
            $id = Hashids::decode(
                $encId
            )[0] ?? null;
            if (!$id) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    'Invalid request id'
                ], 422);
            }
            /*
        |--------------------------------------------------------------------------
        | Get Premium Request
        |--------------------------------------------------------------------------
        */
            $premiumRequest =
                DB::table('premium_requests')
                ->where(
                    'id',
                    $id
                )
                ->first();
            if (!$premiumRequest) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    'Premium request not found'
                ], 404);
            }
            /*
        |--------------------------------------------------------------------------
        | Already Rejected
        |--------------------------------------------------------------------------
        */
            if (
                $premiumRequest->status ===
                'rejected'
            ) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    'Request already rejected'
                ], 422);
            }
            /*
        |--------------------------------------------------------------------------
        | Update Premium Request
        |--------------------------------------------------------------------------
        */
            DB::table('premium_requests')
                ->where(
                    'id',
                    $premiumRequest->id
                )
                ->update([
                    'status' =>
                    'rejected',
                    'remarks' =>
                    $request->remarks,
                    'approved_by' =>
                    $admin->id,
                    'approved_at' =>
                    now(),
                    'updated_at' =>
                    now(),
                ]);
            /*
        |--------------------------------------------------------------------------
        | Fetch Updated Request
        |--------------------------------------------------------------------------
        */
            $updatedRequest =
                DB::table('premium_requests')
                ->where(
                    'id',
                    $premiumRequest->id
                )
                ->first();
            $updatedRequest->id =
                Hashids::encode(
                    $updatedRequest->id
                );
            DB::commit();
            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::SUBSCRIPTION_REJECTED,
                'premium_request',
                $premiumRequest->id,
                'Rejected premium subscription payment for ' . ($premiumRequest->firm_name ?? ('firm #' . $premiumRequest->firm_id)) . '.',
                $request
            );

            return response()->json([
                'status' => true,
                'message' =>
                'Premium request rejected successfully',
                'data' => [
                    'request' =>
                    $updatedRequest
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error(
                'Reject Premium Request Error',
                [
                    'message' =>
                    $e->getMessage(),
                    'line' =>
                    $e->getLine(),
                    'file' =>
                    $e->getFile(),
                ]
            );
            return response()->json([
                'status' => false,
                'message' =>
                'Unexpected server error'
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Creator Marketplace Payments
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Stream a student's resume / marksheet for an authenticated admin.
     *
     * Reads the file directly from the public disk's filesystem path (the same
     * mechanism the firm-side downloadFile uses), so it works regardless of the
     * `public/storage` symlink. Admin-token guarded; students/firms are unaffected
     * (they keep their existing flows). Supports inline view (default) or forced
     * download via `?download=1`.
     */
    public function downloadStudentFile(Request $request, $id)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $type = $request->query('type', 'resume');
            if (! in_array($type, ['resume', 'marksheet'], true)) {
                return response()->json(['status' => false, 'message' => 'Invalid file type'], 422);
            }

            $student = DB::table('student_profiles')->where('user_id', $id)->first();
            if (! $student) {
                return response()->json(['status' => false, 'message' => 'Student not found'], 404);
            }

            $path = $type === 'resume' ? $student->resume_path : $student->marksheet_path;
            if (! $path) {
                return response()->json(['status' => false, 'message' => 'No ' . $type . ' uploaded'], 404);
            }

            $fullPath = storage_path('app/public/' . ltrim($path, '/'));
            if (! file_exists($fullPath)) {
                return response()->json(['status' => false, 'message' => ucfirst($type) . ' file not found'], 404);
            }

            return $request->boolean('download')
                ? response()->download($fullPath)
                : response()->file($fullPath);
        } catch (\Exception $e) {
            Log::error('Admin downloadStudentFile error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Something went wrong'], 500);
        }
    }

    private function adminFromRequest(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (! $token) return null;
        return DB::table('admin_users')->where('api_token', $token)->where('is_active', true)->first();
    }

    /**
     * Constrain a query by login-activity bucket on the given last-login column.
     *
     * Buckets (relative to now): active ≤3d · warm 4–15d · inactive 16–45d ·
     * dormant >45d · never (NULL). Unknown/empty values are a no-op so the
     * caller's existing result set is unchanged.
     */
    private function applyActivityFilter($query, string $column, $activity): void
    {
        if (!in_array($activity, ['active', 'warm', 'inactive', 'dormant', 'never'], true)) {
            return;
        }

        $now = now();
        switch ($activity) {
            case 'active':
                $query->where($column, '>=', $now->copy()->subDays(3));
                break;
            case 'warm':
                $query->where($column, '<', $now->copy()->subDays(3))
                      ->where($column, '>=', $now->copy()->subDays(15));
                break;
            case 'inactive':
                $query->where($column, '<', $now->copy()->subDays(15))
                      ->where($column, '>=', $now->copy()->subDays(45));
                break;
            case 'dormant':
                $query->where($column, '<', $now->copy()->subDays(45));
                break;
            case 'never':
                $query->whereNull($column);
                break;
        }
    }

    /**
     * Apply the admin-list sort. `recent_login` / `oldest_login` order by the
     * last-login column with never-logged-in rows (NULL) pushed to the end in
     * both directions; any other value falls back to the default column DESC.
     */
    private function applyLastLoginSort($query, string $column, $sort, string $defaultColumn): void
    {
        if ($sort === 'recent_login') {
            $query->orderByRaw("$column IS NULL ASC")->orderByDesc($column);
        } elseif ($sort === 'oldest_login') {
            $query->orderByRaw("$column IS NULL ASC")->orderBy($column, 'asc');
        } else {
            $query->orderByDesc($defaultColumn);
        }
    }

    /**
     * List contact-form submissions (admin "Feedback" screen).
     */
    public function getContactSubmissions(Request $request)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $query = DB::table('contact_submissions');
            if ($request->filled('search')) {
                $s = '%' . trim($request->input('search')) . '%';
                $query->where(function ($q) use ($s) {
                    $q->where('name', 'like', $s)
                        ->orWhere('email', 'like', $s)
                        ->orWhere('subject', 'like', $s)
                        ->orWhere('message', 'like', $s);
                });
            }
            $items = $query->orderByDesc('created_at')->paginate(20);

            return response()->json([
                'status' => true,
                'data'   => [
                    'submissions' => $items->items(),
                    'total'       => $items->total(),
                    'page'        => $items->currentPage(),
                    'has_more'    => $items->hasMorePages(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('getContactSubmissions Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function getCreatorPayments(Request $request)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $status = $request->input('status', 'awaiting_verification');

            $payments = DB::table('creator_engagement_payments as p')
                ->join('creator_engagements as e',   'e.id',  '=', 'p.engagement_id')
                ->join('creator_projects as proj',    'proj.id','=', 'e.creator_requirement_id')
                ->join('firm_profiles as fp',         'fp.id', '=', 'p.firm_id')
                ->join('users as fu',                 'fu.id', '=', 'fp.user_id')
                ->join('users as cu',                 'cu.id', '=', 'e.creator_id')
                ->select([
                    'p.id', 'p.status', 'p.payment_method', 'p.amount', 'p.currency',
                    'p.utr_number', 'p.payment_reference', 'p.payment_date',
                    'p.admin_remarks', 'p.reviewed_at', 'p.created_at',
                    DB::raw("CASE WHEN p.screenshot_url IS NOT NULL THEN CONCAT('" . url('/storage') . "/', p.screenshot_url) ELSE NULL END as screenshot_url"),
                    'p.engagement_id',
                    'proj.title as project_title',
                    'fp.firm_name', 'fu.email as firm_email',
                    'cu.name as creator_name',
                    'e.status as engagement_status',
                ])
                ->when($status !== 'all', fn($q) => $q->where('p.status', $status))
                ->orderByDesc('p.created_at')
                ->paginate(20);

            return response()->json([
                'status' => true,
                'data'   => [
                    'payments'   => $payments->items(),
                    'total'      => $payments->total(),
                    'page'       => $payments->currentPage(),
                    'last_page'  => $payments->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin@getCreatorPayments: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function approveCreatorPayment(Request $request, $paymentId)
    {
        DB::beginTransaction();
        try {
            $admin = $this->adminFromRequest($request);
            if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $payment = DB::table('creator_engagement_payments')->where('id', $paymentId)->first();
            if (! $payment) return response()->json(['status' => false, 'message' => 'Payment not found'], 404);

            if ($payment->status !== 'awaiting_verification') {
                return response()->json(['status' => false, 'message' => 'Payment is not awaiting verification'], 422);
            }

            DB::table('creator_engagement_payments')
                ->where('id', $paymentId)
                ->update([
                    'status'      => 'escrow_held',
                    'reviewed_by' => $admin->id,
                    'reviewed_at' => now(),
                    'updated_at'  => now(),
                ]);

            $engagement = DB::table('creator_engagements')->where('id', $payment->engagement_id)->first();

            DB::table('creator_engagements')
                ->where('id', $payment->engagement_id)
                ->update(['status' => 'active', 'updated_at' => now()]);

            $project = DB::table('creator_projects')->where('id', $engagement->creator_requirement_id)->first();

            // Notify creator
            DB::table('creator_marketplace_notifications')->insert([
                'user_id'    => $engagement->creator_id,
                'type'       => 'payment_received',
                'title'      => 'Payment verified — project is now active!',
                'body'       => "Manual payment for \"{$project->title}\" has been verified. Your project is now active.",
                'data'       => json_encode(['engagement_id' => (int) $payment->engagement_id]),
                'read_at'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Notify firm
            $firmUserId = DB::table('firm_profiles')->where('id', $payment->firm_id)->value('user_id');
            if ($firmUserId) {
                DB::table('creator_marketplace_notifications')->insert([
                    'user_id'    => $firmUserId,
                    'type'       => 'payment_verified',
                    'title'      => 'Payment verified — project is now active!',
                    'body'       => "Your manual payment for \"{$project->title}\" has been approved.",
                    'data'       => json_encode(['engagement_id' => (int) $payment->engagement_id]),
                    'read_at'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::CREATOR_PAYMENT_APPROVED,
                'creator_payment',
                $paymentId,
                'Approved creator marketplace payment #' . $paymentId . ' (₹' . $payment->amount . ') — engagement #' . $payment->engagement_id . ' is now active.',
                $request
            );

            return response()->json(['status' => true, 'message' => 'Payment approved. Project is now active.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin@approveCreatorPayment: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function rejectCreatorPayment(Request $request, $paymentId)
    {
        DB::beginTransaction();
        try {
            $admin = $this->adminFromRequest($request);
            if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $validator = Validator::make($request->all(), [
                'remarks' => 'required|string|max:1000',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $payment = DB::table('creator_engagement_payments')->where('id', $paymentId)->first();
            if (! $payment) return response()->json(['status' => false, 'message' => 'Payment not found'], 404);

            if ($payment->status !== 'awaiting_verification') {
                return response()->json(['status' => false, 'message' => 'Payment is not awaiting verification'], 422);
            }

            // Reset to pending so firm can resubmit with corrected proof
            DB::table('creator_engagement_payments')
                ->where('id', $paymentId)
                ->update([
                    'status'       => 'pending',
                    'admin_remarks'=> $request->remarks,
                    'reviewed_by'  => $admin->id,
                    'reviewed_at'  => now(),
                    'updated_at'   => now(),
                ]);

            DB::table('creator_engagements')
                ->where('id', $payment->engagement_id)
                ->update(['status' => 'awaiting_payment', 'updated_at' => now()]);

            // Notify firm
            $firmUserId = DB::table('firm_profiles')->where('id', $payment->firm_id)->value('user_id');
            $project    = DB::table('creator_projects')
                ->join('creator_engagements', 'creator_engagements.creator_requirement_id', '=', 'creator_projects.id')
                ->where('creator_engagements.id', $payment->engagement_id)
                ->value('creator_projects.title');

            if ($firmUserId) {
                DB::table('creator_marketplace_notifications')->insert([
                    'user_id'    => $firmUserId,
                    'type'       => 'payment_rejected',
                    'title'      => 'Payment proof rejected',
                    'body'       => "Your manual payment proof for \"{$project}\" was rejected: {$request->remarks}",
                    'data'       => json_encode(['engagement_id' => (int) $payment->engagement_id]),
                    'read_at'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::CREATOR_PAYMENT_REJECTED,
                'creator_payment',
                $paymentId,
                'Rejected creator marketplace payment #' . $paymentId . ' — engagement #' . $payment->engagement_id . '. Reason: ' . $request->remarks,
                $request
            );

            return response()->json(['status' => true, 'message' => 'Payment rejected. Firm can resubmit proof.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Admin@rejectCreatorPayment: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Read-only engagement summary (for payout context)
    // Uses admin_token auth only — no user auth, no impersonation.
    // ─────────────────────────────────────────────────────────────────────────

    public function getEngagementSummary(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $admin = $this->adminFromRequest($request);
        if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $engagement = DB::table('creator_engagements as e')
                ->join('creator_projects as p',  'p.id',  '=', 'e.creator_requirement_id')
                ->join('firm_profiles as fp',     'fp.id', '=', 'e.firm_id')
                ->join('users as cu',             'cu.id', '=', 'e.creator_id')
                ->where('e.id', $id)
                ->select([
                    'e.id', 'e.status', 'e.accepted_bid_amount', 'e.delivery_days', 'e.created_at',
                    'p.title as project_title', 'p.category', 'p.description',
                    'fp.firm_name',
                    'cu.name as creator_name', 'cu.email as creator_email',
                ])
                ->first();

            if (! $engagement) {
                return response()->json(['status' => false, 'message' => 'Engagement not found'], 404);
            }

            $submissionCount = (int) DB::table('engagement_submissions')->where('engagement_id', $id)->count();
            $paymentStatus   = DB::table('creator_engagement_payments')->where('engagement_id', $id)->value('status');

            return response()->json([
                'status' => true,
                'data'   => [
                    'id'                  => (int) $engagement->id,
                    'status'              => $engagement->status,
                    'project_title'       => $engagement->project_title,
                    'category'            => $engagement->category,
                    'description'         => $engagement->description,
                    'accepted_bid_amount' => (float) $engagement->accepted_bid_amount,
                    'delivery_days'       => (int) $engagement->delivery_days,
                    'firm_name'           => $engagement->firm_name,
                    'creator_name'        => $engagement->creator_name,
                    'creator_email'       => $engagement->creator_email,
                    'submission_count'    => $submissionCount,
                    'payment_status'      => $paymentStatus,
                    'created_at'          => $engagement->created_at,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin@getEngagementSummary: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Reported Student Profiles (Moderation)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Allowed moderation states for a report.
     * 'reviewed' is retained for backward compatibility with older records; the
     * controlled incorrect-information workflow uses pending → dismissed /
     * awaiting_student / warning_issued.
     */
    private const REPORT_STATUSES = ['pending', 'reviewed', 'dismissed', 'awaiting_student', 'warning_issued'];

    /** Human label for the value currently on the student's profile for a reported field. */
    private function currentFieldValue(object $r): ?string
    {
        switch ($r->reported_field) {
            case 'Name':
                return $r->student_name;
            case 'City':
                return $r->city;
            case 'Education Details':
                return $r->qualification;
            case 'CA Foundation Status':
            case 'CA Intermediate Status':
            case 'CA Final Status':
            case 'Articleship Status':
                return $r->ca_status;
            case 'Work Experience':
                $parts = array_filter([
                    $r->experience_years !== null && $r->experience_years !== '' ? $r->experience_years . ' yrs' : null,
                    $r->current_firm_name ?: null,
                ]);
                return $parts ? implode(' @ ', $parts) : null;
            default:
                return null; // Skills / Other / unmapped — admin uses the profile link.
        }
    }

    public function getReportedProfiles(Request $request)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $status = $request->input('status', 'all');

            $query = DB::table('reported_profiles as rp')
                ->leftJoin('users as su', 'su.id', '=', 'rp.student_id')
                ->leftJoin('users as ru', 'ru.id', '=', 'rp.reported_by')
                ->leftJoin('firm_profiles as fp', 'fp.user_id', '=', 'rp.reported_by')
                ->leftJoin('student_profiles as sp', 'sp.user_id', '=', 'rp.student_id')
                ->select(
                    'rp.id',
                    'rp.student_id',
                    'rp.reported_by',
                    'rp.reason',
                    'rp.reported_field',
                    'rp.description',
                    'rp.remarks',
                    'rp.evidence_path',
                    'rp.admin_remarks',
                    'rp.status',
                    'rp.created_at',
                    'rp.updated_at',
                    'su.name as student_name',
                    'su.email as student_email',
                    'ru.name as reporter_name',
                    'ru.email as reporter_email',
                    'fp.firm_name as reporting_firm',
                    'sp.city',
                    'sp.qualification',
                    'sp.ca_status',
                    'sp.experience_years',
                    'sp.current_firm_name'
                )
                ->orderByDesc('rp.created_at');

            if (in_array($status, self::REPORT_STATUSES, true)) {
                $query->where('rp.status', $status);
            }

            $reports = $query->get()->map(function ($r) {
                $r->created_at_formatted = $r->created_at ? date('d M Y h:i A', strtotime($r->created_at)) : null;
                $r->reporting_firm       = $r->reporting_firm ?: $r->reporter_name;
                $r->current_value        = $this->currentFieldValue($r);
                $r->evidence_url         = $r->evidence_path ? url('/storage/' . $r->evidence_path) : null;
                // Strip the joined helper columns from the payload.
                unset($r->city, $r->qualification, $r->ca_status, $r->experience_years, $r->current_firm_name, $r->evidence_path);
                return $r;
            });

            $counts = DB::table('reported_profiles')
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status');

            return response()->json([
                'status' => true,
                'data'   => [
                    'reports' => $reports,
                    'counts'  => [
                        'pending'          => (int) ($counts['pending']          ?? 0),
                        'awaiting_student' => (int) ($counts['awaiting_student'] ?? 0),
                        'warning_issued'   => (int) ($counts['warning_issued']   ?? 0),
                        'dismissed'        => (int) ($counts['dismissed']        ?? 0),
                        'reviewed'         => (int) ($counts['reviewed']         ?? 0),
                        'total'            => (int) $counts->sum(),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Admin@getReportedProfiles: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /**
     * Apply an admin decision to a report. A single status endpoint keeps the
     * moderation workflow scalable. Every action is admin-driven — no automatic
     * penalties, suspensions, hiding, or ranking changes are ever applied here.
     *
     *   dismissed        → close the case (insufficient/invalid). No student notice.
     *   awaiting_student → ask the student to review/update. Profile stays active.
     *   warning_issued   → record a warning + notify the student. No restrictions.
     */
    public function updateReportStatus(Request $request, $id)
    {
        try {
            $admin = $this->adminFromRequest($request);
            if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $validator = Validator::make($request->all(), [
                'status'  => 'required|in:' . implode(',', self::REPORT_STATUSES),
                'remarks' => 'nullable|string|max:1000',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $report = DB::table('reported_profiles')->where('id', $id)->first();
            if (! $report) return response()->json(['status' => false, 'message' => 'Report not found'], 404);

            $update = [
                'status'        => $request->status,
                'reviewed_by'   => $admin->id,
                'reviewed_at'   => now(),
                'updated_at'    => now(),
            ];
            if ($request->filled('remarks')) {
                // Admin reasoning is kept separate from the reporter's own remarks.
                $update['admin_remarks'] = $request->remarks;
            }

            DB::table('reported_profiles')->where('id', $id)->update($update);

            // Student-facing notifications. These are informational only — the
            // student's profile remains fully active and unrestricted.
            $fieldNote = $report->reported_field ? " regarding your \"{$report->reported_field}\"" : '';
            if ($request->status === 'awaiting_student') {
                NotificationHelper::create(
                    (int) $report->student_id,
                    'Profile Review Requested',
                    'A firm has reported potentially inaccurate profile information' . $fieldNote .
                    '. Please review and update your profile if necessary.'
                );
            } elseif ($request->status === 'warning_issued') {
                $reasonNote = $request->filled('remarks') ? ' Note from our team: ' . $request->remarks : '';
                NotificationHelper::create(
                    (int) $report->student_id,
                    'Warning Issued',
                    'After review, inaccurate information was found on your profile' . $fieldNote .
                    '. Please correct it to keep your profile accurate.' . $reasonNote
                );
            }

            $reportAction = match ($request->status) {
                'dismissed'      => AdminActivityLogger::REPORT_DISMISSED,
                'warning_issued' => AdminActivityLogger::WARNING_ISSUED,
                default          => AdminActivityLogger::REPORT_REVIEWED,
            };
            AdminActivityLogger::log(
                $admin,
                $reportAction,
                'reported_profile',
                $id,
                'Moderation: report #' . $id . ' (student #' . $report->student_id . ') set to ' . str_replace('_', ' ', $request->status) . '.' .
                    ($request->filled('remarks') ? ' Remarks: ' . $request->remarks : ''),
                $request
            );

            return response()->json([
                'status'  => true,
                'message' => 'Report updated to ' . str_replace('_', ' ', $request->status) . '.',
            ]);
        } catch (\Exception $e) {
            Log::error('Admin@updateReportStatus: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
