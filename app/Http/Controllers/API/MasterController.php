<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class MasterController extends Controller
{
    public function getCities(Request $request)
    {
        try {
            $query = DB::table('city_master')
                ->select(
                    'id',
                    'city_name',
                    'state_name',
                    DB::raw("CONCAT(city_name, ', ', state_name) as label"),
                )
                ->where('is_active', true);
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->Where('city_name', 'like', "%{$search}%");
                });
            }
            if ($request->filled('state_name')) {
                $search = trim($request->state_name);
                $query->where(function ($q) use ($search) {
                    $q->Where('state_name', 'like', "%{$search}%");
                });
            }
            $cities = $query
                ->orderBy('city_name')
                ->get();
            return response()->json([
                'status' => true,
                'data' => $cities
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch cities'
            ]);
        }
    }
    public function getCompanies(Request $request)
    {
        try {
            $query = DB::table('firm_profiles')
                ->select('id', 'firm_name')
                ->where('is_active', true);
            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->Where('firm_name', 'like', "%{$search}%");
                });
            }
            $companies = $query
                ->orderBy('firm_name')
                ->get();
            return response()->json([
                'status' => true,
                'data' => $companies
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch companies'
            ]);
        }
    }
    public function getAdminSubscriptions(Request $request)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
            $search =
                trim(
                    $request->search ?? ''
                );
            /*
        |--------------------------------------------------------------------------
        | Query
        |--------------------------------------------------------------------------
        */
            $query = DB::table('firm_subscriptions')
                ->join(
                    'firm_profiles',
                    'firm_subscriptions.firm_id',
                    '=',
                    'firm_profiles.id'
                )
                ->join(
                    'users',
                    'firm_profiles.user_id',
                    '=',
                    'users.id'
                )
                ->select(
                    /*
                |--------------------------------------------------------------------------
                | Subscription
                |--------------------------------------------------------------------------
                */
                    'firm_subscriptions.id',
                    'firm_subscriptions.firm_id',
                    'firm_subscriptions.plan',
                    'firm_subscriptions.status',
                    'firm_subscriptions.starts_at',
                    'firm_subscriptions.expires_at',
                    'firm_subscriptions.created_at',
                    'firm_subscriptions.updated_at',
                    /*
                |--------------------------------------------------------------------------
                | Firm
                |--------------------------------------------------------------------------
                */
                    'firm_profiles.firm_name',
                    /*
                |--------------------------------------------------------------------------
                | User
                |--------------------------------------------------------------------------
                */
                    'users.email as firm_email'
                )
                ->orderByDesc(
                    'firm_subscriptions.id'
                );
            /*
        |--------------------------------------------------------------------------
        | Search Filter
        |--------------------------------------------------------------------------
        */
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q
                        ->where(
                            'firm_profiles.firm_name',
                            'LIKE',
                            '%' . $search . '%'
                        )
                        ->orWhere(
                            'users.email',
                            'LIKE',
                            '%' . $search . '%'
                        );
                });
            }
            /*
        |--------------------------------------------------------------------------
        | Fetch
        |--------------------------------------------------------------------------
        */
            $subscriptions =
                $query->get();
            /*
        |--------------------------------------------------------------------------
        | Format
        |--------------------------------------------------------------------------
        */
            $formatted =
                $subscriptions->map(function ($item) {
                    return [
                        'id' =>
                        (string) $item->id,
                        'firm_id' =>
                        (string) $item->firm_id,
                        'firm_name' =>
                        $item->firm_name,
                        'firm_email' =>
                        $item->firm_email,
                        'plan' =>
                        $item->plan,
                        'status' =>
                        $item->status,
                        'starts_at' =>
                        $item->starts_at
                            ? date(
                                'd M Y',
                                strtotime(
                                    $item->starts_at
                                )
                            )
                            : null,
                        'expires_at' =>
                        $item->expires_at
                            ? date(
                                'd M Y',
                                strtotime(
                                    $item->expires_at
                                )
                            )
                            : null,
                        'created_at' =>
                        $item->created_at
                            ? date(
                                'd M Y h:i A',
                                strtotime(
                                    $item->created_at
                                )
                            )
                            : null,
                        'updated_at' =>
                        $item->updated_at
                            ? date(
                                'd M Y h:i A',
                                strtotime(
                                    $item->updated_at
                                )
                            )
                            : null,
                    ];
                });
            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'status' => true,
                'message' =>
                'Subscriptions fetched successfully',
                'data' => [
                    'subscriptions' =>
                    $formatted,
                    'total' =>
                    $formatted->count(),
                ]
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Get Admin Subscriptions Error',
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
            ]);
        }
    }
    public function addSubscriptions(Request $request)
    {
        try {
            /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */
            $validator = Validator::make(
                $request->all(),
                [
                    'firm_id' =>
                    'required|exists:firm_profiles,id',
                    'plan' =>
                    'required|in:free,premium',
                    'status' =>
                    'required|in:active,expired,cancelled',
                    'starts_at' =>
                    'nullable|date',
                    'expires_at' =>
                    'nullable|date',
                ]
            );
            /*
        |--------------------------------------------------------------------------
        | Validation Failed
        |--------------------------------------------------------------------------
        */
            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    $validator
                        ->errors()
                        ->first(),
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Check Firm
        |--------------------------------------------------------------------------
        */
            $firm = DB::table('firm_profiles')
                ->where(
                    'id',
                    $request->firm_id
                )
                ->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' =>
                    'Firm not found'
                ]);
            }
            /*
        |--------------------------------------------------------------------------
        | Expire Existing Active Premium
        |--------------------------------------------------------------------------
        */
            DB::table('firm_subscriptions')
                ->where(
                    'firm_id',
                    $request->firm_id
                )
                ->where(
                    'status',
                    'active'
                )
                ->update([
                    'status' =>
                    'expired',
                    'updated_at' =>
                    now(),
                ]);
            /*
        |--------------------------------------------------------------------------
        | Create Subscription
        |--------------------------------------------------------------------------
        */
            $subscriptionId =
                DB::table('firm_subscriptions')
                ->insertGetId([
                    'firm_id' =>
                    $request->firm_id,
                    'plan' =>
                    $request->plan,
                    'status' =>
                    $request->status,
                    'starts_at' =>

                    !empty($request->starts_at)

                        ? Carbon::parse(
                            $request->starts_at
                        )->format(
                            'Y-m-d H:i:s'
                        )

                        : now(),

                    'expires_at' =>

                    !empty($request->expires_at)

                        ? Carbon::parse(
                            $request->expires_at
                        )->format(
                            'Y-m-d H:i:s'
                        )

                        : null,
                    'created_at' =>
                    now(),
                    'updated_at' =>
                    now(),
                ]);
            /*
        |--------------------------------------------------------------------------
        | Fetch Created Subscription
        |--------------------------------------------------------------------------
        */
            $subscription =
                DB::table('firm_subscriptions')
                ->where(
                    'id',
                    $subscriptionId
                )
                ->first();
            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */
            return response()->json([
                'status' => true,
                'message' =>
                'Subscription added successfully',
                'data' => [
                    'subscription' => [
                        'id' =>
                        (string)
                        $subscription->id,
                        'firm_id' =>
                        (string)
                        $subscription->firm_id,
                        'plan' =>
                        $subscription->plan,
                        'status' =>
                        $subscription->status,
                        'starts_at' =>
                        $subscription->starts_at,
                        'expires_at' =>
                        $subscription->expires_at,
                        'created_at' =>
                        $subscription->created_at,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error(
                'Add Subscription Error',
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
            ]);
        }
    }
}
