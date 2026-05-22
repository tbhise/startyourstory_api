<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;


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
            $search = trim($request->search ?? '');
            $query = DB::table('firm_subscriptions')
                ->join('firm_profiles', 'firm_subscriptions.firm_id', '=', 'firm_profiles.user_id')
                ->join('users', 'firm_profiles.user_id', '=', 'users.id')
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
                    'total' => $formatted->count(),
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
                    'firm_id' => 'required|exists:firm_profiles,id',
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
                ->where('id', $request->firm_id)
                ->first();
            if (!$firm) {
                return response()->json([
                    'status' => false,
                    'message' => 'Firm not found'
                ]);
            }

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



                'contact_person' =>
                $request->contact_person,

                'transaction_id' =>
                $request->transaction_id,

                'payment_date' =>
                $request->payment_date,

                'plan' =>
                $request->plan,

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

            /*
        |--------------------------------------------------------------------------
        | Authenticate Admin
        |--------------------------------------------------------------------------
        */

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
        | Admin Only
        |--------------------------------------------------------------------------
        */

            if ($user->role !== 'admin') {

                return response()->json([

                    'status' => false,

                    'message' => 'Only admin can access premium requests'
                ], 403);
            }

            /*
        |--------------------------------------------------------------------------
        | Fetch Premium Requests
        |--------------------------------------------------------------------------
        */

            $requests = DB::table('premium_requests as pr')

                ->leftJoin(
                    'firm_profiles as fp',
                    'fp.id',
                    '=',
                    'pr.firm_id'
                )

                ->select(

                    'pr.id',
                    'pr.firm_id',
                    'pr.contact_person',
                    'pr.firm_name',
                    'pr.plan',
                    'pr.transaction_id',
                    'pr.payment_date',
                    'pr.screenshot_url',
                    'pr.status',
                    'pr.remarks',
                    'pr.created_at',

                    'fp.logo',
                    'fp.city',
                    'fp.state'
                )

                ->orderByDesc('pr.created_at')

                ->get();

            /*
        |--------------------------------------------------------------------------
        | Response
        |--------------------------------------------------------------------------
        */

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
}
