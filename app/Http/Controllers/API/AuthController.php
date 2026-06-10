<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
            $token     = base64_encode(Str::random(40));
            $expiresAt = now()->addDays(7);

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'api_token'        => $token,
                    'token_expires_at' => $expiresAt,
                    'updated_at'       => now(),
                ]);

            // ── Session & login-history tracking ──────────────────────────
            $ua      = $request->header('User-Agent', '');
            $parsed  = $this->parseUserAgent($ua);
            $ip      = $request->ip();
            $location = $this->resolveLocation($ip);
            $now     = now();

            /*
             * Future plan-limit hook (do NOT enforce yet):
             *
             * $limit = PlanHelper::sessionLimit($user);   // 1 | 3 | PHP_INT_MAX
             * $count = DB::table('user_sessions')->where('user_id', $user->id)->count();
             * if ($count >= $limit) {
             *     DB::table('user_sessions')
             *         ->where('user_id', $user->id)
             *         ->orderBy('last_activity_at')
             *         ->limit($count - $limit + 1)
             *         ->delete();
             * }
             */

            DB::table('user_sessions')->insert([
                'user_id'          => $user->id,
                'token'            => $token,
                'device_type'      => $parsed['device_type'],
                'browser'          => $parsed['browser'],
                'os'               => $parsed['os'],
                'ip_address'       => $ip,
                'location'         => $location,
                'expires_at'       => $expiresAt,
                'last_activity_at' => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            DB::table('login_history')->insert([
                'user_id'      => $user->id,
                'ip_address'   => $ip,
                'device_type'  => $parsed['device_type'],
                'browser'      => $parsed['browser'],
                'os'           => $parsed['os'],
                'location'     => $location,
                'logged_in_at' => $now,
                'created_at'   => $now,
            ]);
            // ── End session tracking ──────────────────────────────────────

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
                DB::table('user_sessions')->where('token', $token)->delete();
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
            $isPremium = false;
            $premiumExpiresAt = null;
            $premiumStartsAt = null;
            $premiumPlan = null;
            $premiumDaysRemaining = null;
            $isBranch = false;
            $parentFirmId = null;
            $parentFrn = null;
            $firmCity = null;
            if ($user->role === 'firm') {
                $firmProfile = DB::table('firm_profiles')
                    ->where('user_id', $user->id)
                    ->select('id', 'verification_status', 'rejection_reason', 'is_premium', 'is_branch', 'parent_firm_id', 'parent_frn', 'city')
                    ->first();
                $verificationStatus = $firmProfile->verification_status ?? 'pending';
                $rejectionReason    = $firmProfile->rejection_reason ?? null;
                $isBranch           = (bool) ($firmProfile->is_branch ?? false);
                $parentFirmId       = $firmProfile->parent_firm_id ?? null;
                $parentFrn          = $firmProfile->parent_frn ?? null;
                $firmCity           = $firmProfile->city ?? null;
                if ($firmProfile && $firmProfile->is_premium) {
                    $isPremium = true;
                    $firmSub = DB::table('firm_subscriptions')
                        ->where('firm_id', $firmProfile->id)
                        ->where('status', 'active')
                        ->where(function ($q) {
                            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                        })
                        ->orderByDesc('created_at')
                        ->first();
                    if ($firmSub) {
                        $premiumExpiresAt = $firmSub->expires_at;
                        $premiumStartsAt  = $firmSub->starts_at ?? null;
                        $premiumPlan      = $firmSub->plan ?? 'premium';
                        if ($firmSub->expires_at) {
                            $premiumDaysRemaining = max(0, (int) now()->diffInDays($firmSub->expires_at, false));
                        }
                    }
                }
            }

            $lookingFor = null;
            $preferredCategories = null;
            if ($user->role === 'student') {
                $studentProfile = DB::table('student_profiles')
                    ->where('user_id', $user->id)
                    ->select('looking_for', 'preferred_categories', 'is_creator')
                    ->first();
                $lookingFor = $studentProfile->looking_for ?? null;
                $preferredCategories = $studentProfile && $studentProfile->preferred_categories
                    ? json_decode($studentProfile->preferred_categories)
                    : [];
                $isCreatorOptin = (bool)($studentProfile->is_creator ?? false);
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
                    'is_creator' => $isCreatorOptin ?? false,
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
                    ...($user->role === 'firm' ? [
                        'is_branch'      => $isBranch,
                        'parent_firm_id' => $parentFirmId,
                        'parent_frn'     => $parentFrn,
                        'firm_city'      => $firmCity,
                    ] : []),
                    'referral_code' => $user->referral_code ?? null,
                    'referral_count' => $user->referral_count ?? 0,
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
    public function changePassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:6|max:15',
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $token = $request->cookie('auth_token');
            $user  = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'User not found'], 404);
            }

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Current password is incorrect.',
                ], 401);
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'password'   => Hash::make($request->new_password),
                    'updated_at' => now(),
                ]);

            return response()->json([
                'status'  => true,
                'message' => 'Password changed successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Change Password Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $token = $request->cookie('auth_token');

            DB::table('users')
                ->where('api_token', $token)
                ->update(['api_token' => null]);

            // Remove from session tracker
            DB::table('user_sessions')->where('token', $token)->delete();

            return response()->json([
                'status'  => true,
                'message' => 'Logout successful',
            ])->cookie('auth_token', '', -1);
        } catch (\Exception) {
            return response()->json(['status' => false]);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                              */
    /* ------------------------------------------------------------------ */

    /**
     * Parses a User-Agent string into device_type, browser, and os.
     * No external package required.
     */
    private function parseUserAgent(string $ua): array
    {
        // Device type
        $deviceType = 'desktop';
        if (preg_match('/Mobile|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $ua)) {
            $deviceType = 'mobile';
        } elseif (preg_match('/iPad|Android(?!.*Mobile)|Tablet|Kindle|Silk/i', $ua)) {
            $deviceType = 'tablet';
        }

        // Browser — order matters (Edge/Opera must come before Chrome/Safari)
        $browser = 'Unknown Browser';
        if (preg_match('/Edg\/([\d]+)/i', $ua, $m)) {
            $browser = 'Edge ' . $m[1];
        } elseif (preg_match('/OPR\/([\d]+)|Opera/i', $ua, $m)) {
            $browser = 'Opera ' . ($m[1] ?? '');
        } elseif (preg_match('/SamsungBrowser\/([\d]+)/i', $ua, $m)) {
            $browser = 'Samsung Browser ' . $m[1];
        } elseif (preg_match('/Chrome\/([\d]+)/i', $ua, $m)) {
            $browser = 'Chrome ' . $m[1];
        } elseif (preg_match('/Firefox\/([\d]+)/i', $ua, $m)) {
            $browser = 'Firefox ' . $m[1];
        } elseif (preg_match('/Version\/([\d]+).*Safari/i', $ua, $m)) {
            $browser = 'Safari ' . $m[1];
        } elseif (preg_match('/MSIE ([\d]+)|Trident.*rv:([\d]+)/i', $ua, $m)) {
            $browser = 'Internet Explorer ' . ($m[1] ?: $m[2]);
        }

        // Operating system
        $os = 'Unknown OS';
        if (preg_match('/Windows NT ([\d.]+)/i', $ua, $m)) {
            $map = ['10.0' => '10 / 11', '6.3' => '8.1', '6.2' => '8', '6.1' => '7', '6.0' => 'Vista', '5.1' => 'XP'];
            $os  = 'Windows ' . ($map[$m[1]] ?? $m[1]);
        } elseif (preg_match('/Mac OS X ([\d_]+)/i', $ua, $m)) {
            $os = 'macOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/Android ([\d.]+)/i', $ua, $m)) {
            $os = 'Android ' . explode('.', $m[1])[0];
        } elseif (preg_match('/iPhone OS ([\d_]+)/i', $ua, $m)) {
            $os = 'iOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/iPad.*OS ([\d_]+)/i', $ua, $m)) {
            $os = 'iPadOS ' . str_replace('_', '.', $m[1]);
        } elseif (preg_match('/CrOS/i', $ua)) {
            $os = 'ChromeOS';
        } elseif (preg_match('/Linux/i', $ua)) {
            $os = 'Linux';
        }

        return ['device_type' => $deviceType, 'browser' => $browser, 'os' => $os];
    }

    /**
     * Best-effort IP → city/country lookup via ip-api.com (free, no key).
     * Returns null on failure so login is never blocked.
     */
    private function resolveLocation(string $ip): ?string
    {
        // Skip private / loopback addresses
        if (
            $ip === '127.0.0.1' || $ip === '::1' ||
            str_starts_with($ip, '192.168.') ||
            str_starts_with($ip, '10.') ||
            str_starts_with($ip, '172.')
        ) {
            return 'Local Network';
        }

        try {
            $res = Http::timeout(3)
                ->get("http://ip-api.com/json/{$ip}?fields=status,city,regionName,country");

            if ($res->ok()) {
                $d = $res->json();
                if (($d['status'] ?? '') === 'success') {
                    return implode(', ', array_filter([
                        $d['city']       ?? null,
                        $d['regionName'] ?? null,
                        $d['country']    ?? null,
                    ])) ?: null;
                }
            }
        } catch (\Exception) {
            // geolocation is best-effort; never block login
        }

        return null;
    }
}
