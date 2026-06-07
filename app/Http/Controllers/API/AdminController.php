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

            DB::commit();
            $subscription =
                DB::table('firm_subscriptions')
                ->where('id', $subscriptionId)
                ->first();
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
            if ($user->role !== 'admin') {
                return response()->json([
                    'status' => false,
                    'message' => 'Only admin can access premium requests'
                ], 403);
            }
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
            $existingSubscription =
                DB::table('firm_subscriptions')
                ->where('firm_id', $premiumRequest->firm_id)
                ->first();
            if ($existingSubscription) {
                DB::table('firm_subscriptions')
                    ->where('id', $existingSubscription->id)
                    ->update([
                        'contact_person' => $premiumRequest->contact_person,
                        'plan' => $premiumRequest->plan === 'premium-yearly' ? 'premium' : $premiumRequest->plan,
                        'transaction_id' => $premiumRequest->transaction_id,
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
                        'firm_id' => $premiumRequest->firm_id,
                        'contact_person' => $premiumRequest->contact_person,
                        'plan' => $premiumRequest->plan === 'premium-yearly' ? 'premium' : $premiumRequest->plan,
                        'transaction_id' => $premiumRequest->transaction_id,
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
                ->where('id', $premiumRequest->firm_id)
                ->update(['is_premium' => 1, 'updated_at' => now()]);
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
            return response()->json(['status' => true, 'message' => 'Firm rejected successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('rejectFirm Error', ['message' => $e->getMessage()]);
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
}
