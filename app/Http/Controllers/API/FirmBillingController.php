<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Firm Billing & Payments — read-only reporting/visibility for a firm's own
 * subscription and creator-marketplace payments.
 *
 * IMPORTANT: This controller introduces NO new storage and NO wallet concepts.
 * It only READS from existing tables (firm_subscriptions, creator_engagement_payments)
 * and exposes a firm's OWN records. It never touches subscription activation,
 * payment, payout, commission, settlement or any internal accounting logic.
 */
class FirmBillingController extends Controller
{
    /**
     * Human-readable plan name + duration label, derived from the stored plan key.
     * Mirrors the plan keys used by PhonePeFirmController (premium-monthly/quarterly/yearly).
     */
    private function planMeta(?string $plan): array
    {
        // Resolves via the admin-managed plan catalog first, with legacy-key
        // fallbacks (2026-07-11). Historical rows keep their stored amount.
        return \App\Helpers\PlanHelper::meta($plan);
    }

    /**
     * Normalise the assorted DB statuses into the four display statuses the UI
     * filters on: paid | pending | failed | refunded.
     */
    private function normaliseSubStatus(?string $paymentStatus): string
    {
        return match ($paymentStatus) {
            'paid'                => 'paid',
            'refunded'            => 'refunded',
            'failed', 'cancelled' => 'failed',
            default               => 'pending', // pending / manual_verification
        };
    }

    private function normaliseCreatorStatus(?string $status): string
    {
        return match ($status) {
            'paid', 'verified', 'escrow_held' => 'paid',
            'refunded'                        => 'refunded',
            default                           => 'pending', // pending / awaiting_verification
        };
    }

    /**
     * GET /firm/billing-payments  [auth + firm-verified]
     * Returns the firm's premium subscriptions, branch subscriptions (premium
     * purchased by branch offices under this firm's FRN) and creator-marketplace
     * payments, plus headline summary figures.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->attributes->get('auth_user');

            $firm = DB::table('firm_profiles')
                ->where('user_id', $user->id)
                ->first();

            if (! $firm) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $firmName  = $firm->firm_name;
            $firmEmail = $user->email ?? null;

            // ── SECTION 1 — Premium subscriptions (this firm's own purchases) ──
            // Exclude raw 'pending' rows: those are abandoned/in-flight checkouts,
            // not actual purchases. manual_verification is kept (shows as Pending).
            $premiumRows = DB::table('firm_subscriptions')
                ->where('firm_id', $firm->id)
                ->where('payment_status', '!=', 'pending')
                ->orderByDesc('created_at')
                ->get();

            $premium = $premiumRows->map(function ($r) use ($firmName, $firmEmail) {
                $meta   = $this->planMeta($r->plan);
                $status = $this->normaliseSubStatus($r->payment_status);
                $amount = (float) $r->amount;
                $ref    = $r->gateway_payment_id ?: ($r->transaction_id ?: $r->gateway_order_id);
                $isActive = $r->status === 'active'
                    && (! $r->expires_at || strtotime($r->expires_at) > time());

                return [
                    'id'              => (int) $r->id,
                    'invoice_number'  => 'INV-PRM-' . str_pad((string) $r->id, 5, '0', STR_PAD_LEFT),
                    'purchase_date'   => $r->payment_date ?: $r->created_at,
                    'plan_name'       => $meta['name'],
                    'duration'        => $meta['duration'],
                    'amount'          => $amount,
                    'payment_status'  => $status,
                    'subscription_status' => $r->status,
                    'is_active'       => $isActive,
                    'starts_at'       => $r->starts_at,
                    'expires_at'      => $r->expires_at,
                    'payment_reference' => $ref,
                    'firm_name'       => $firmName,
                    'firm_email'      => $firmEmail,
                    'description'     => $meta['name'] . ' (' . $meta['duration'] . ')',
                ];
            })->values();

            // ── SECTION 2 — Branch subscriptions ──
            // A parent firm sees the premium subscriptions purchased by branch
            // accounts registered under its FRN (is_branch=1, parent_frn = my frn).
            // Only meaningful for a parent account; branches see their own premium
            // under Section 1 instead.
            $branch = collect();
            if (empty($firm->is_branch) && ! empty($firm->frn)) {
                $branchRows = DB::table('firm_subscriptions as s')
                    ->join('firm_profiles as bf', 'bf.id', '=', 's.firm_id')
                    ->where('bf.parent_frn', $firm->frn)
                    ->where('bf.is_branch', 1)
                    ->where('s.payment_status', '!=', 'pending')
                    ->orderByDesc('s.created_at')
                    ->select([
                        's.id', 's.plan', 's.amount', 's.payment_status', 's.status',
                        's.payment_date', 's.created_at', 's.starts_at', 's.expires_at',
                        's.gateway_payment_id', 's.transaction_id', 's.gateway_order_id',
                        'bf.firm_name as branch_name', 'bf.city as branch_city',
                    ])
                    ->get();

                $branch = $branchRows->map(function ($r) use ($firmName, $firmEmail) {
                    $meta   = $this->planMeta($r->plan);
                    $status = $this->normaliseSubStatus($r->payment_status);
                    $amount = (float) $r->amount;
                    $ref    = $r->gateway_payment_id ?: ($r->transaction_id ?: $r->gateway_order_id);

                    return [
                        'id'               => (int) $r->id,
                        'invoice_number'   => 'INV-BRN-' . str_pad((string) $r->id, 5, '0', STR_PAD_LEFT),
                        'purchase_date'    => $r->payment_date ?: $r->created_at,
                        'branch_name'      => $r->branch_name . ($r->branch_city ? ' — ' . $r->branch_city : ''),
                        'subscription_type' => $meta['name'],
                        'duration'         => $meta['duration'],
                        'amount'           => $amount,
                        'payment_status'   => $status,
                        'expires_at'       => $r->expires_at,
                        'payment_reference' => $ref,
                        'firm_name'        => $firmName,
                        'firm_email'       => $firmEmail,
                        'description'      => 'Branch Subscription — ' . $meta['name'],
                    ];
                })->values();
            }

            // ── SECTION 3 — Creator marketplace payments (this firm's own) ──
            // Reads the full amount the firm paid for an engagement. Never exposes
            // payout, commission or settlement (those tables are not touched here).
            $creatorRows = DB::table('creator_engagement_payments as cp')
                ->join('creator_engagements as e', 'e.id', '=', 'cp.engagement_id')
                ->join('creator_projects as p', 'p.id', '=', 'e.creator_requirement_id')
                ->join('users as cu', 'cu.id', '=', 'e.creator_id')
                ->where('cp.firm_id', $firm->id)
                ->where('cp.status', '!=', 'pending')
                ->orderByDesc('cp.created_at')
                ->select([
                    'cp.id', 'cp.amount', 'cp.status', 'cp.payment_date', 'cp.created_at',
                    'cp.gateway_payment_id', 'cp.payment_reference', 'cp.utr_number',
                    'p.title as project_title',
                    'cu.name as creator_name',
                ])
                ->get();

            $creator = $creatorRows->map(function ($r) use ($firmName, $firmEmail) {
                $status = $this->normaliseCreatorStatus($r->status);
                $amount = (float) $r->amount;
                $ref    = $r->gateway_payment_id ?: ($r->payment_reference ?: $r->utr_number);

                return [
                    'id'              => (int) $r->id,
                    'invoice_number'  => 'INV-CRE-' . str_pad((string) $r->id, 5, '0', STR_PAD_LEFT),
                    'payment_date'    => $r->payment_date ?: $r->created_at,
                    'project_title'   => $r->project_title,
                    'creator_name'    => $r->creator_name,
                    'amount'          => $amount,
                    'payment_status'  => $status,
                    'payment_reference' => $ref,
                    'firm_name'       => $firmName,
                    'firm_email'      => $firmEmail,
                    'description'     => 'Creator Project — ' . $r->project_title,
                ];
            })->values();

            // ── Summary figures (sum of successful payments per category) ──
            $sumPaid = fn ($rows) => round($rows
                ->filter(fn ($x) => $x['payment_status'] === 'paid')
                ->sum('amount'), 2);

            // Active plan = this firm's current active premium subscription.
            $activeSub = DB::table('firm_subscriptions')
                ->where('firm_id', $firm->id)
                ->where('status', 'active')
                ->where('payment_status', 'paid')
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->orderByDesc('expires_at')
                ->first();

            $activePlanName = $activeSub
                ? $this->planMeta($activeSub->plan)['name']
                : 'Free Plan';

            return response()->json([
                'status' => true,
                'data'   => [
                    'firm' => [
                        'name'      => $firmName,
                        'email'     => $firmEmail,
                        'is_branch' => (bool) $firm->is_branch,
                    ],
                    'summary' => [
                        'active_plan'          => $activePlanName,
                        'active_plan_expires'  => $activeSub->expires_at ?? null,
                        'total_premium'        => $sumPaid($premium),
                        'total_branch'         => $sumPaid($branch),
                        'total_creator'        => $sumPaid($creator),
                    ],
                    'premium' => $premium,
                    'branch'  => $branch->values(),
                    'creator' => $creator,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('FirmBillingController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Could not load billing details'], 500);
        }
    }
}
