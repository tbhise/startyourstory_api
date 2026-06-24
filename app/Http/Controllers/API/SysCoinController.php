<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\SysCoinHelper;
use App\Helpers\AuthHelper;

class SysCoinController extends Controller
{
    /** Any authenticated, non-deleted user may view their own coin balance. */
    private function getUser(Request $request): ?object
    {
        return AuthHelper::resolveUser($request);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /sys-coins — coin balance + meta
    |--------------------------------------------------------------------------
    */
    public function getAccount(Request $request)
    {
        try {
            $user = $this->getUser($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $account = SysCoinHelper::getOrCreate($user->id);

            return response()->json([
                'status' => true,
                'data'   => [
                    'available_coins' => (int) $account->available_coins,
                    'hold_coins'      => (int) $account->hold_coins,
                    'consumed_coins'  => (int) $account->consumed_coins,
                    'lifetime_earned' => (int) $account->lifetime_earned,
                    'application_cost' => SysCoinHelper::APPLICATION_COST,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('SysCoinController@getAccount: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /sys-coins/ledger — paginated coin transaction history
    |--------------------------------------------------------------------------
    */
    public function getLedger(Request $request)
    {
        try {
            $user = $this->getUser($request);
            if (!$user) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

            $perPage = 20;
            $page    = max(1, (int) $request->input('page', 1));

            $total = DB::table('sys_coin_transactions')->where('user_id', $user->id)->count();

            $transactions = DB::table('sys_coin_transactions')
                ->where('user_id', $user->id)
                ->orderByDesc('id')
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
            Log::error('SysCoinController@getLedger: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
