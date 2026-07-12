<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\PlanHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Firm subscription plan catalog (added 2026-07-11).
 *
 * Public/firm side  → GET /firm/subscription-plans  (active plans for pricing).
 * Admin side        → CRUD over firm_subscription_plans.
 *
 * Pricing is never hardcoded on the frontend; it reads from here. Historical
 * firm_subscriptions rows keep their own snapshot amount, so editing a plan's
 * price here never mutates past purchases. In-use plans are deactivated, never
 * deleted.
 */
class FirmSubscriptionPlanController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  Public / firm — active plans for the pricing + checkout page.      */
    /*  GET /firm/subscription-plans                                        */
    /* ------------------------------------------------------------------ */
    public function publicIndex(Request $request)
    {
        try {
            $plans = PlanHelper::activePlans()->map(function ($p) {
                $mrp   = (float) $p->mrp;
                $price = (float) $p->price;
                return [
                    'id'             => $p->plan_key,   // plan_key doubles as the id used by PhonePe initiate
                    'plan_key'       => $p->plan_key,
                    'label'          => $p->name,
                    'name'           => $p->name,
                    'duration_months' => (int) $p->duration_months,
                    'price'          => $price,
                    'mrp'            => $mrp,
                    'original_price' => $mrp,                          // alias for the existing UI field
                    'discount'       => $mrp > $price ? round($mrp - $price, 2) : 0,
                    'sort_order'     => (int) $p->sort_order,
                ];
            })->values();

            return response()->json(['status' => true, 'data' => ['plans' => $plans]]);
        } catch (\Exception $e) {
            Log::error('FirmSubscriptionPlanController@publicIndex: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Could not load plans'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Admin — list ALL plans (active + inactive) with usage counts.      */
    /*  POST /admin/subscription-plans                                      */
    /* ------------------------------------------------------------------ */
    public function adminIndex(Request $request)
    {
        if ($err = $this->requireAdmin($request)) return $err;
        try {
            $plans = DB::table('firm_subscription_plans')
                ->orderBy('sort_order')->orderBy('id')->get()
                ->map(function ($p) {
                    // Usage count: never allow deleting a plan that has subscriptions.
                    $inUse = DB::table('firm_subscriptions')->where('plan', $p->plan_key)->count();
                    return [
                        'id'              => (int) $p->id,
                        'plan_key'        => $p->plan_key,
                        'name'            => $p->name,
                        'duration_months' => (int) $p->duration_months,
                        'mrp'             => (float) $p->mrp,
                        'price'           => (float) $p->price,
                        'is_active'       => (bool) $p->is_active,
                        'sort_order'      => (int) $p->sort_order,
                        'subscriptions_count' => $inUse,
                        'deletable'       => $inUse === 0,
                    ];
                });
            return response()->json(['status' => true, 'data' => ['plans' => $plans]]);
        } catch (\Exception $e) {
            Log::error('FirmSubscriptionPlanController@adminIndex: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Could not load plans'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Admin — create a plan.  POST /admin/subscription-plans/create      */
    /* ------------------------------------------------------------------ */
    public function store(Request $request)
    {
        if ($err = $this->requireAdmin($request)) return $err;
        try {
            $validator = Validator::make($request->all(), [
                // 'free' is reserved — it means "no active row" throughout
                // SubscriptionHelper::isPremiumFirm (plan != 'free'). A catalog row
                // keyed 'free' would be purchasable yet NEVER grant premium, silently
                // taking a firm's money for nothing. Blocked here (2026-07-11).
                'plan_key'        => 'required|string|max:50|regex:/^[a-z0-9\-]+$/|unique:firm_subscription_plans,plan_key|not_in:free',
                'name'            => 'required|string|max:100',
                'duration_months' => 'required|integer|min:1|max:120',
                'mrp'             => 'required|numeric|min:0',
                'price'           => 'required|numeric|min:0',
                'is_active'       => 'nullable|boolean',
                'sort_order'      => 'nullable|integer|min:0',
            ], [
                'plan_key.not_in' => 'The plan key "free" is reserved and cannot be used for a paid plan.',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            $id = DB::table('firm_subscription_plans')->insertGetId([
                'plan_key'        => $request->plan_key,
                'name'            => $request->name,
                'duration_months' => (int) $request->duration_months,
                'mrp'             => (float) $request->mrp,
                'price'           => (float) $request->price,
                'is_active'       => $request->boolean('is_active', true) ? 1 : 0,
                'sort_order'      => (int) ($request->sort_order ?? 0),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            return response()->json(['status' => true, 'message' => 'Plan created', 'data' => ['id' => $id]]);
        } catch (\Exception $e) {
            Log::error('FirmSubscriptionPlanController@store: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Could not create plan'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Admin — update a plan.  POST /admin/subscription-plans/{id}/update */
    /*  plan_key is immutable once created (historical rows reference it). */
    /* ------------------------------------------------------------------ */
    public function update(Request $request, $id = null)
    {
        if ($err = $this->requireAdmin($request)) return $err;
        try {
            $plan = DB::table('firm_subscription_plans')->where('id', $id)->first();
            if (!$plan) {
                return response()->json(['status' => false, 'message' => 'Plan not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'name'            => 'required|string|max:100',
                'duration_months' => 'required|integer|min:1|max:120',
                'mrp'             => 'required|numeric|min:0',
                'price'           => 'required|numeric|min:0',
                'is_active'       => 'nullable|boolean',
                'sort_order'      => 'nullable|integer|min:0',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
            }

            // NOTE: price/mrp changes affect FUTURE purchases only. Existing
            // firm_subscriptions rows store their own amount snapshot and are not
            // touched here, so historical records stay immutable.
            DB::table('firm_subscription_plans')->where('id', $id)->update([
                'name'            => $request->name,
                'duration_months' => (int) $request->duration_months,
                'mrp'             => (float) $request->mrp,
                'price'           => (float) $request->price,
                'is_active'       => $request->boolean('is_active', (bool) $plan->is_active) ? 1 : 0,
                'sort_order'      => (int) ($request->sort_order ?? $plan->sort_order),
                'updated_at'      => now(),
            ]);

            return response()->json(['status' => true, 'message' => 'Plan updated']);
        } catch (\Exception $e) {
            Log::error('FirmSubscriptionPlanController@update: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Could not update plan'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Admin — delete a plan (ONLY when unused). Otherwise deactivate.    */
    /*  POST /admin/subscription-plans/{id}/delete                         */
    /* ------------------------------------------------------------------ */
    public function destroy(Request $request, $id = null)
    {
        if ($err = $this->requireAdmin($request)) return $err;
        try {
            $plan = DB::table('firm_subscription_plans')->where('id', $id)->first();
            if (!$plan) {
                return response()->json(['status' => false, 'message' => 'Plan not found'], 404);
            }
            $inUse = DB::table('firm_subscriptions')->where('plan', $plan->plan_key)->count();
            if ($inUse > 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'This plan has ' . $inUse . ' subscription(s) and cannot be deleted. Deactivate it instead.',
                ], 422);
            }
            DB::table('firm_subscription_plans')->where('id', $id)->delete();
            return response()->json(['status' => true, 'message' => 'Plan deleted']);
        } catch (\Exception $e) {
            Log::error('FirmSubscriptionPlanController@destroy: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Could not delete plan'], 500);
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Admin auth guard — mirrors the admin_token pattern used across the  */
    /*  existing admin endpoints in AdminController.                        */
    /* ------------------------------------------------------------------ */
    private function requireAdmin(Request $request)
    {
        $token = $request->cookie('admin_token') ?: $request->bearerToken();
        if (!$token) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        $admin = DB::table('admin_users')->where('api_token', $token)->where('is_active', true)->first();
        if (!$admin) {
            return response()->json(['status' => false, 'message' => 'Invalid token'], 401);
        }
        return null;
    }
}
