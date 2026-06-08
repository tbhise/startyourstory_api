<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralController extends Controller
{
    /**
     * GET /referrals
     *
     * Returns the authenticated user's referral code, referral count,
     * aggregated stats, and a chronological list of referred users.
     */
    public function index(Request $request)
    {
        try {
            $token = $request->cookie('auth_token');

            if (!$token) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            $user = DB::table('users')
                ->where('api_token', $token)
                ->where('is_deleted', false)
                ->first();

            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            if ($user->token_expires_at && now()->greaterThan($user->token_expires_at)) {
                return response()->json(['status' => false, 'message' => 'Token expired'], 401);
            }

            $referrals = DB::table('users')
                ->where('referred_by', $user->id)
                ->where('is_deleted', false)
                ->select('id', 'name', 'role', 'created_at')
                ->orderByDesc('created_at')
                ->get();

            $stats = [
                'total_referrals' => $referrals->count(),
                'firms'           => $referrals->where('role', 'firm')->count(),
                'students'        => $referrals->where('role', 'student')->count(),
            ];

            return response()->json([
                'status' => true,
                'data'   => [
                    'referral_code'  => $user->referral_code ?? '',
                    'referral_count' => $user->referral_count ?? 0,
                    'stats'          => $stats,
                    'referrals'      => $referrals->values()->all(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Referral Index Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
