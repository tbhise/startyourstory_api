<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\WalletHelper;
use App\Helpers\NotificationHelper;

class AdminWalletController extends Controller
{
    private function getAdmin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (!$token) return null;
        return DB::table('admin_users')->where('api_token', $token)->first();
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/wallet/recharges — list all with optional status filter
    |--------------------------------------------------------------------------
    */
    public function getRecharges(Request $request)
    {
        try {
            $admin = $this->getAdmin($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $status = $request->input('status', 'all');
            $search = trim($request->input('search', ''));

            $query = DB::table('wallet_recharges as wr')
                ->join('users as u', 'u.id', '=', 'wr.user_id')
                ->select(
                    'wr.*',
                    'u.name as student_name',
                    'u.email as student_email'
                )
                ->orderByDesc('wr.created_at');

            if ($status !== 'all') {
                $query->where('wr.status', $status);
            }

            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('u.name', 'like', "%{$search}%")
                      ->orWhere('u.email', 'like', "%{$search}%")
                      ->orWhere('wr.utr_number', 'like', "%{$search}%")
                      ->orWhere('wr.reference_number', 'like', "%{$search}%");
                });
            }

            $recharges = $query->get()->map(function ($item) {
                $item->screenshot_url = $item->screenshot_url
                    ? asset('storage/' . $item->screenshot_url)
                    : null;
                $item->created_at_formatted = $item->created_at
                    ? date('d M Y h:i A', strtotime($item->created_at))
                    : null;
                return $item;
            });

            $counts = DB::table('wallet_recharges')
                ->selectRaw("status, COUNT(*) as count")
                ->groupBy('status')
                ->get()
                ->keyBy('status');

            return response()->json([
                'status' => true,
                'data'   => [
                    'recharges' => $recharges,
                    'counts'    => [
                        'pending'  => (int) ($counts['pending']->count  ?? 0),
                        'approved' => (int) ($counts['approved']->count ?? 0),
                        'rejected' => (int) ($counts['rejected']->count ?? 0),
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('AdminWalletController@getRecharges: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/wallet/recharges/{id}/approve
    |--------------------------------------------------------------------------
    */
    public function approveRecharge(Request $request, $id)
    {
        try {
            $admin = $this->getAdmin($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $recharge = DB::table('wallet_recharges')->where('id', $id)->first();
            if (!$recharge) return response()->json(['status' => false, 'message' => 'Not found'], 404);
            if ($recharge->status === 'approved') {
                return response()->json(['status' => false, 'message' => 'Already approved']);
            }

            DB::beginTransaction();
            DB::table('wallet_recharges')
                ->where('id', $id)
                ->update([
                    'status'      => 'approved',
                    'approved_by' => $admin->id,
                    'approved_at' => now(),
                    'remarks'     => $request->input('remarks'),
                    'updated_at'  => now(),
                ]);

            WalletHelper::credit(
                $recharge->user_id,
                (float) $recharge->amount,
                $recharge->id,
                "Manual recharge approved — ₹{$recharge->amount}"
            );

            NotificationHelper::create(
                $recharge->user_id,
                'Wallet Recharged ₹' . number_format($recharge->amount),
                "Your wallet has been credited with ₹{$recharge->amount}. Available balance updated."
            );

            DB::commit();
            $wallet = WalletHelper::getOrCreate($recharge->user_id);

            return response()->json([
                'status'  => true,
                'message' => "Approved — ₹{$recharge->amount} credited to student wallet",
                'data'    => ['available_balance' => (float) $wallet->available_balance],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminWalletController@approveRecharge: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/wallet/recharges/{id}/reject
    |--------------------------------------------------------------------------
    */
    public function rejectRecharge(Request $request, $id)
    {
        try {
            $admin = $this->getAdmin($request);
            if (!$admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $recharge = DB::table('wallet_recharges')->where('id', $id)->first();
            if (!$recharge) return response()->json(['status' => false, 'message' => 'Not found'], 404);
            if (in_array($recharge->status, ['approved', 'rejected'])) {
                return response()->json(['status' => false, 'message' => 'Already processed']);
            }

            $reason = $request->input('remarks', 'Invalid payment proof');

            DB::table('wallet_recharges')
                ->where('id', $id)
                ->update([
                    'status'      => 'rejected',
                    'approved_by' => $admin->id,
                    'rejected_at' => now(),
                    'remarks'     => $reason,
                    'updated_at'  => now(),
                ]);

            NotificationHelper::create(
                $recharge->user_id,
                'Wallet Recharge Rejected',
                "Your recharge of ₹{$recharge->amount} was not approved. Reason: {$reason}"
            );

            return response()->json(['status' => true, 'message' => 'Recharge rejected']);
        } catch (\Exception $e) {
            Log::error('AdminWalletController@rejectRecharge: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
