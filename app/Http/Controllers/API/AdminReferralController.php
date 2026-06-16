<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\NotificationHelper;
use App\Services\AdminActivityLogger;

class AdminReferralController extends Controller
{
    private function getAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')->where('api_token', $token)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/referral-payouts — list firm-referral payouts (filter by status)
    |--------------------------------------------------------------------------
    */
    public function listPayouts(Request $request)
    {
        try {
            $admin = $this->getAdmin($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $status = $request->input('status', 'all');
            $search = trim($request->input('search', ''));

            $query = DB::table('referral_payouts as rp')
                ->leftJoin('users as ref', 'ref.id', '=', 'rp.referrer_user_id')
                ->leftJoin('users as fu', 'fu.id', '=', 'rp.referred_user_id')
                ->leftJoin('firm_profiles as fp', 'fp.user_id', '=', 'rp.referred_user_id')
                ->select(
                    'rp.*',
                    'ref.name as referrer_name',
                    'ref.email as referrer_email',
                    'ref.role as referrer_role',
                    'fu.email as referred_email',
                    DB::raw('COALESCE(fp.firm_name, fu.name) as referred_firm_name')
                )
                ->orderByDesc('rp.id');

            if ($status !== 'all') {
                $query->where('rp.status', $status);
            }
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('ref.name', 'like', "%{$search}%")
                      ->orWhere('ref.email', 'like', "%{$search}%")
                      ->orWhere('fp.firm_name', 'like', "%{$search}%")
                      ->orWhere('fu.email', 'like', "%{$search}%");
                });
            }

            $payouts = $query->get()->map(function ($p) {
                $p->created_at_formatted = $p->created_at ? date('d M Y h:i A', strtotime($p->created_at)) : null;
                return $p;
            });

            $counts = DB::table('referral_payouts')
                ->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            return response()->json([
                'status' => true,
                'data'   => [
                    'payouts' => $payouts,
                    'counts'  => [
                        'pending'  => (int) ($counts['pending']->count  ?? 0),
                        'approved' => (int) ($counts['approved']->count ?? 0),
                        'paid'     => (int) ($counts['paid']->count     ?? 0),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminReferralController@listPayouts: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/referral-payouts/{id}/approve  (pending → approved)
    |--------------------------------------------------------------------------
    */
    public function approvePayout(Request $request, $id)
    {
        try {
            $admin = $this->getAdmin($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $payout = DB::table('referral_payouts')->where('id', $id)->first();
            if (!$payout) return response()->json(['status' => false, 'message' => 'Not found'], 404);
            if ($payout->status !== 'pending') {
                return response()->json(['status' => false, 'message' => 'Only pending payouts can be approved']);
            }

            DB::table('referral_payouts')
                ->where('id', $id)
                ->update([
                    'status'      => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                    'remarks'     => $request->input('remarks'),
                    'updated_at'  => now(),
                ]);

            AdminActivityLogger::log($admin, AdminActivityLogger::REFERRAL_PAYOUT_APPROVED, 'referral_payout', $id, "Approved referral payout #{$id} (₹{$payout->reward_amount}).", $request);

            return response()->json(['status' => true, 'message' => 'Payout approved']);
        } catch (\Exception $e) {
            Log::error('AdminReferralController@approvePayout: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/referral-payouts/{id}/mark-paid  (approved → paid)
    | Mark-only: the ₹ amount is transferred externally. No wallet credit.
    |--------------------------------------------------------------------------
    */
    public function markPayoutPaid(Request $request, $id)
    {
        try {
            $admin = $this->getAdmin($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $payout = DB::table('referral_payouts')->where('id', $id)->first();
            if (!$payout) return response()->json(['status' => false, 'message' => 'Not found'], 404);
            if ($payout->status === 'paid') {
                return response()->json(['status' => false, 'message' => 'Already marked paid']);
            }
            if ($payout->status !== 'approved') {
                return response()->json(['status' => false, 'message' => 'Approve the payout before marking it paid']);
            }

            DB::table('referral_payouts')
                ->where('id', $id)
                ->update([
                    'status'         => 'paid',
                    'paid_at'        => now(),
                    'paid_reference' => $request->input('paid_reference'),
                    'remarks'        => $request->input('remarks', $payout->remarks),
                    'updated_at'     => now(),
                ]);

            // Notify the referrer (informational — no in-app money moves).
            NotificationHelper::create(
                $payout->referrer_user_id,
                'Referral reward paid',
                "Your ₹" . number_format((float) $payout->reward_amount) . " firm-referral reward has been paid."
            );

            AdminActivityLogger::log($admin, AdminActivityLogger::REFERRAL_PAYOUT_PAID, 'referral_payout', $id, "Marked referral payout #{$id} as paid.", $request);

            return response()->json(['status' => true, 'message' => 'Payout marked as paid']);
        } catch (\Exception $e) {
            Log::error('AdminReferralController@markPayoutPaid: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/sys-coins/transactions — all coin ledger entries (search/filter)
    |--------------------------------------------------------------------------
    */
    public function listCoinTransactions(Request $request)
    {
        try {
            $admin = $this->getAdmin($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $search  = trim($request->input('search', ''));
            $type    = trim($request->input('type', ''));
            $perPage = 30;
            $page    = max(1, (int) $request->input('page', 1));

            $base = DB::table('sys_coin_transactions as t')
                ->join('users as u', 'u.id', '=', 't.user_id');

            if ($search !== '') {
                $base->where(function ($q) use ($search) {
                    $q->where('u.name', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%");
                });
            }
            if ($type !== '') {
                $base->where('t.type', $type);
            }

            $total = (clone $base)->count();

            $transactions = $base
                ->select('t.*', 'u.name as user_name', 'u.email as user_email', 'u.role as user_role')
                ->orderByDesc('t.id')
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
            Log::error('AdminReferralController@listCoinTransactions: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/referral-transactions — coin referral-bonus reward history
    |--------------------------------------------------------------------------
    */
    public function listReferralTransactions(Request $request)
    {
        try {
            $admin = $this->getAdmin($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $perPage = 30;
            $page    = max(1, (int) $request->input('page', 1));

            $base = DB::table('sys_coin_transactions as t')
                ->join('users as ref', 'ref.id', '=', 't.user_id')          // recipient = referrer
                ->leftJoin('users as rd', 'rd.id', '=', 't.reference_id')    // referred student
                ->where('t.type', 'REFERRAL_BONUS');

            $total = (clone $base)->count();

            $transactions = $base
                ->select(
                    't.*',
                    'ref.name as referrer_name',
                    'ref.email as referrer_email',
                    'ref.role as referrer_role',
                    'rd.name as referred_name',
                    'rd.role as referred_role'
                )
                ->orderByDesc('t.id')
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
            Log::error('AdminReferralController@listReferralTransactions: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
