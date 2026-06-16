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
            // Log::info('Admin Login Attempt', ['email' => $request->email , 'password' => $request->password]);
            $admin = DB::table('admin_users')
                ->where('email', $request->email)
                ->first();
            // Log::info('Admin Login Query Result', ['admin' => $admin]);
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
            Log::info($admin->id);
            DB::table('admin_users')
                ->where('id', $admin->id)
                ->update([
                    'api_token' => $token,
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
            $query = DB::table('firm_subscriptions')
                ->leftJoin('firm_profiles', 'firm_subscriptions.firm_id', '=', 'firm_profiles.user_id')
                ->leftJoin('users', 'firm_profiles.user_id', '=', 'users.id')
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
                    'firm_profiles.firm_name',
                    'users.email as firm_email'
                )
                ->orderByDesc('firm_subscriptions.id');
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q
                        ->where('firm_profiles.firm_name', 'LIKE', '%' . $search . '%')
                        ->orWhere('users.email', 'LIKE', '%' . $search . '%');
                });
            }
            $subscriptions = $query->get();


            $totalFirms = DB::table('firm_profiles')->count();


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
                    'total' => $totalFirms,
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
            $token = $request->cookie('auth_token');
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
            $requests = DB::table('premium_requests as pr')
                ->leftJoin('firm_profiles as fp', 'fp.id', '=', 'pr.firm_id')
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
                    'requests' => $requests
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
                        ->orWhere('u.mobile', 'like', $s);
                });
            }

            if ($request->filled('city')) {
                $query->where('fp.city', $request->city);
            }

            $firms = $query
                ->select(
                    'fp.user_id as id',
                    'fp.firm_name',
                    'fp.firm_type',
                    'fp.city',
                    'fp.verification_status',
                    'fp.created_at',
                    'u.email',
                    'u.mobile',
                    DB::raw("CASE WHEN fp.is_premium = 1 THEN 'premium' ELSE 'free' END as plan")
                )
                ->orderByDesc('fp.created_at')
                ->get();

            return response()->json([
                'status' => true,
                'data' => ['firms' => $firms, 'total' => $firms->count()]
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

            $students = $query
                ->select(
                    'u.id',
                    'u.name',
                    'u.email',
                    'u.mobile',
                    'u.profile_completed',
                    'u.created_at',
                    'u.is_deleted',
                    'u.deletion_requested_at',
                    'u.scheduled_deletion_at',
                    'sp.looking_for',
                    'sp.city',
                    DB::raw('IF(u.email_verified_at IS NOT NULL, 1, 0) as is_verified')
                )
                ->orderByDesc('u.created_at')
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
