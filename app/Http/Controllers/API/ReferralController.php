<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\ReferralHelper;
use App\Services\SystemSettingService;

class ReferralController extends Controller
{
    /**
     * GET /referral/validate  [public — used during registration]
     *
     * Validates a referral code for live form feedback. When the registrant's
     * email/mobile is passed, flags self-referral so the form can auto-clear it.
     * Returns: { valid, self, referrer_name, referrer_role }.
     */
    public function validate(Request $request)
    {
        try {
            $result = ReferralHelper::validateCode(
                $request->input('code'),
                $request->input('email'),
                $request->input('mobile')
            );

            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('ReferralController@validate: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

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

            $userId       = $user->id;
            $startOfMonth = now()->startOfMonth();

            $referrals = DB::table('users')
                ->where('referred_by', $userId)
                ->where('is_deleted', false)
                ->select('id', 'name', 'role', 'created_at')
                ->orderByDesc('created_at')
                ->get();

            $students = $referrals->where('role', 'student')->count();
            $firms    = $referrals->where('role', 'firm')->count();

            // This-month deltas (real, from created_at).
            $studentsThisMonth = DB::table('users')
                ->where('referred_by', $userId)->where('is_deleted', false)
                ->where('role', 'student')->where('created_at', '>=', $startOfMonth)->count();
            $firmsThisMonth = DB::table('users')
                ->where('referred_by', $userId)->where('is_deleted', false)
                ->where('role', 'firm')->where('created_at', '>=', $startOfMonth)->count();

            // SYS Coins earned (lifetime + this month) — referrer's own coin account.
            $coinAccount = DB::table('sys_coin_accounts')->where('user_id', $userId)->first();
            $coinsEarned = (int) ($coinAccount->lifetime_earned ?? 0);
            $earnTypes   = ['WELCOME_BONUS', 'REFERRAL_BONUS', 'ADMIN_CREDIT', 'BLOG_REWARD'];
            $coinsThisMonth = (int) DB::table('sys_coin_transactions')
                ->where('user_id', $userId)
                ->whereIn('type', $earnTypes)
                ->where('created_at', '>=', $startOfMonth)
                ->sum('amount');

            // Real-money firm-referral payouts owed to this referrer (pending + approved).
            $pending = DB::table('referral_payouts')
                ->where('referrer_user_id', $userId)
                ->whereIn('status', ['pending', 'approved'])
                ->selectRaw('COALESCE(SUM(reward_amount), 0) as amount, COUNT(*) as cnt')
                ->first();
            $pendingAmount    = (float) ($pending->amount ?? 0);
            $pendingFirmCount = (int) ($pending->cnt ?? 0);

            // Per-referral status + reward (for the history table).
            $bonusedStudentIds = DB::table('sys_coin_transactions')
                ->where('user_id', $userId)
                ->where('type', 'REFERRAL_BONUS')
                ->where('reference_type', 'referral')
                ->pluck('reference_id')
                ->filter()
                ->map(fn ($v) => (int) $v)
                ->flip();
            $firmPayoutStatus = DB::table('referral_payouts')
                ->where('referrer_user_id', $userId)
                ->pluck('status', 'referred_user_id');

            $enriched = $referrals->map(function ($r) use ($bonusedStudentIds, $firmPayoutStatus) {
                if ($r->role === 'firm') {
                    $status = match ($firmPayoutStatus->get($r->id)) {
                        'paid'     => 'Completed',
                        'approved' => 'Under Review',
                        default    => 'Pending',
                    };
                    $rewardType  = 'money';
                    $rewardLabel = '₹' . number_format(SystemSettingService::getFirmPremiumPurchaseReward());
                } else {
                    $status      = $bonusedStudentIds->has($r->id) ? 'Completed' : 'Pending';
                    $rewardType  = 'coins';
                    $rewardLabel = '+' . SystemSettingService::getStudentReferralReward() . ' SYS Coins';
                }
                return [
                    'id'           => $r->id,
                    'name'         => $r->name,
                    'role'         => $r->role,
                    'created_at'   => $r->created_at,
                    'status'       => $status,
                    'reward_type'  => $rewardType,
                    'reward_label' => $rewardLabel,
                ];
            });

            return response()->json([
                'status' => true,
                'data'   => [
                    'referral_code'  => $user->referral_code ?? '',
                    'referral_count' => $user->referral_count ?? 0,
                    'stats'          => [
                        'total_referrals'     => $referrals->count(),
                        'firms'               => $firms,
                        'students'            => $students,
                        'students_this_month' => $studentsThisMonth,
                        'firms_this_month'    => $firmsThisMonth,
                    ],
                    'coins'           => ['earned' => $coinsEarned, 'this_month' => $coinsThisMonth],
                    'pending_rewards' => ['amount' => $pendingAmount, 'firm_count' => $pendingFirmCount],
                    'lifetime'        => ['coins' => $coinsEarned, 'pending_amount' => $pendingAmount],
                    // Live reward amounts from Platform Settings — drives the reward
                    // cards/labels on the /referrals page (no hardcoded values).
                    'rewards'         => [
                        'student_referral_coins' => SystemSettingService::getStudentReferralReward(),
                        'firm_premium_reward'    => SystemSettingService::getFirmPremiumPurchaseReward(),
                    ],
                    'referrals'       => $enriched->values()->all(),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Referral Index Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
