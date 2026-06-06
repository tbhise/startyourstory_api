<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required',
                'role' => 'required',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ], 422);
            }
            $user = DB::table('users')
                ->where('email', $request->email)
                ->where('role', $request->role)
                ->where('is_deleted', false)
                ->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found'
                ], 404);
            }
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid password'
                ], 401);
            }
            $token = base64_encode(Str::random(40));
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'api_token' => $token,
                    'token_expires_at' => now()->addDays(7),
                    'updated_at' => now()
                ]);

            $verificationStatus = null;
            $rejectionReason = null;
            if ($user->role === 'firm') {
                $firmProfile = DB::table('firm_profiles')
                    ->where('user_id', $user->id)
                    ->select('verification_status', 'rejection_reason')
                    ->first();
                $verificationStatus = $firmProfile->verification_status ?? 'pending';
                $rejectionReason = $firmProfile->rejection_reason ?? null;
            }

            return response()
                ->json([
                    'status' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'mobile' => $user->mobile,
                        'role' => $user->role,
                        'profile_completed' => $user->profile_completed,
                        'profile_image' => $user->profile_image
                            ? asset('storage/' . $user->profile_image)
                            : null,
                        'verification_status' => $verificationStatus,
                        'rejection_reason' => $rejectionReason,
                    ]
                ])
                ->cookie(
                    'auth_token', // cookie name
                    $token,       // cookie value
                    60 * 24 * 7, // 7 days in minutes
                    '/',         // path
                    null,        // domain
                    false,       // secure (set true on HTTPS production)
                    false,        // httpOnly
                    false,       // raw
                    'Lax'        // sameSite
                );
        } catch (\Exception $e) {
            Log::error('Login Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
    public function me(Request $request)
    {
        try {
            $token = $request->cookie('auth_token');
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthenticated'
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
            if (
                $user->token_expires_at &&
                now()->greaterThan($user->token_expires_at)
            ) {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update([
                        'api_token' => null
                    ]);
                return response()->json([
                    'status' => false,
                    'message' => 'Token expired'
                ], 401);
            }
            $verificationStatus = null;
            $rejectionReason = null;
            if ($user->role === 'firm') {
                $firmProfile = DB::table('firm_profiles')
                    ->where('user_id', $user->id)
                    ->select('verification_status', 'rejection_reason')
                    ->first();
                $verificationStatus = $firmProfile->verification_status ?? 'pending';
                $rejectionReason = $firmProfile->rejection_reason ?? null;
            }

            $lookingFor = null;
            $preferredCategories = null;
            $isPremium = false;
            $premiumExpiresAt = null;
            $premiumStartsAt = null;
            $premiumPlan = null;
            $premiumDaysRemaining = null;
            if ($user->role === 'student') {
                $studentProfile = DB::table('student_profiles')
                    ->where('user_id', $user->id)
                    ->select('looking_for', 'preferred_categories')
                    ->first();
                $lookingFor = $studentProfile->looking_for ?? null;
                $preferredCategories = $studentProfile && $studentProfile->preferred_categories
                    ? json_decode($studentProfile->preferred_categories)
                    : [];
                $sub = DB::table('student_subscriptions')
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    })
                    ->first();
                if ($sub) {
                    $isPremium = true;
                    $premiumExpiresAt = $sub->expires_at;
                    $premiumStartsAt = $sub->starts_at;
                    $premiumPlan = $sub->plan ?? 'premium';
                    if ($sub->expires_at) {
                        $premiumDaysRemaining = max(0, (int) now()->diffInDays($sub->expires_at, false));
                    }
                }
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'role' => $user->role,
                    'looking_for' => $lookingFor,
                    'preferred_categories' => $preferredCategories,
                    'is_premium' => $isPremium,
                    'premium_plan' => $premiumPlan,
                    'premium_starts_at' => $premiumStartsAt ?? null,
                    'premium_expires_at' => $premiumExpiresAt,
                    'premium_days_remaining' => $premiumDaysRemaining,
                    'email_verified_at' => $user->email_verified_at,
                    'profile_completed' => $user->profile_completed,
                    'profile_image' => $user->profile_image
                        ? asset('storage/' . $user->profile_image)
                        : null,
                    'verification_status' => $verificationStatus,
                    'rejection_reason' => $rejectionReason,
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Me API Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
    public function logout(Request $request)
    {
        try {
            $token =
                $request->cookie('auth_token');
            DB::table('users')
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
                'auth_token',
                '',
                -1
            );
        } catch (\Exception) {
            return response()->json([
                'status' => false
            ]);
        }
    }
}
