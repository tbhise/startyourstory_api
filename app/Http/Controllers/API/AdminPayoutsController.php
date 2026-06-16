<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AdminPayoutsController extends Controller
{
    private function admin(Request $request): ?object
    {
        $token = $request->cookie('admin_token');
        if (! $token) return null;
        return DB::table('admin_users')
            ->where('api_token', $token)
            ->where('is_active', true)
            ->first();
    }

    private function decryptField(string $value): string
    {
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            return $value;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/creator-payouts?status=pending&page=1
    // ─────────────────────────────────────────────────────────────────────────

    public function getPayouts(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $status  = $request->input('status', 'pending');
        $page    = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        $query = DB::table('creator_payouts as p')
            ->join('creator_engagements as ce', 'ce.id', '=', 'p.engagement_id')
            ->join('creator_projects as proj', 'proj.id', '=', 'ce.creator_requirement_id')
            ->join('firm_profiles as fp', 'fp.id', '=', 'ce.firm_id')
            ->join('users as cu', 'cu.id', '=', 'p.creator_id')
            ->leftJoin('creator_bank_details as cbd', 'cbd.creator_id', '=', 'p.creator_id')
            ->select([
                'p.id', 'p.engagement_id',
                'p.gross_amount', 'p.commission_rate', 'p.commission_amount', 'p.net_amount',
                'p.status', 'p.transaction_reference', 'p.paid_at', 'p.admin_notes',
                'p.created_at', 'p.updated_at',
                'cu.name   as creator_name',
                'cu.email  as creator_email',
                'proj.title as project_title',
                'fp.firm_name',
                'cbd.account_holder_name',
                'cbd.bank_name',
                'cbd.account_number',
                'cbd.ifsc_code',
                'cbd.is_verified as bank_verified',
            ])
            ->when($status !== 'all', fn($q) => $q->where('p.status', $status))
            ->orderByDesc('p.created_at');

        $total = (clone $query)->count();
        $items = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        foreach ($items as $item) {
            if ($item->account_number) {
                $item->account_number = $this->decryptField($item->account_number);
            }
            if ($item->ifsc_code) {
                $item->ifsc_code = $this->decryptField($item->ifsc_code);
            }
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'payouts'   => $items,
                'total'     => $total,
                'page'      => $page,
                'last_page' => (int) ceil($total / $perPage),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/creator-payouts/{id}/mark-paid
    // ─────────────────────────────────────────────────────────────────────────

    public function markPaid(Request $request, $id): JsonResponse
    {
        $admin = $this->admin($request);
        if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $validator = Validator::make($request->all(), [
            'transaction_reference' => 'required|string|max:500',
            'admin_notes'           => 'nullable|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $payout = DB::table('creator_payouts')->where('id', $id)->first();
        if (! $payout) {
            return response()->json(['status' => false, 'message' => 'Payout not found'], 404);
        }
        if ($payout->status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'Payout is not in pending state'], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('creator_payouts')->where('id', $id)->update([
                'status'                => 'paid',
                'transaction_reference' => $request->transaction_reference,
                'admin_notes'           => $request->admin_notes,
                'paid_at'               => now(),
                'processed_by'          => $admin->id,
                'updated_at'            => now(),
            ]);

            DB::table('creator_engagements')->where('id', $payout->engagement_id)->update([
                'status'     => 'completed',
                'updated_at' => now(),
            ]);

            DB::table('engagement_timeline')->insert([
                'engagement_id' => $payout->engagement_id,
                'user_id'       => null,
                'role'          => 'system',
                'event'         => 'payout_paid',
                'note'          => "Payout transferred. Ref: {$request->transaction_reference}",
                'meta'          => json_encode([
                    'transaction_reference' => $request->transaction_reference,
                    'net_amount'            => (float) $payout->net_amount,
                    'payout_id'             => (int) $id,
                ]),
                'created_at' => now(),
            ]);

            $engagement = DB::table('creator_engagements as ce')
                ->join('creator_projects as p', 'p.id', '=', 'ce.creator_requirement_id')
                ->join('firm_profiles as fp', 'fp.id', '=', 'ce.firm_id')
                ->where('ce.id', $payout->engagement_id)
                ->select(['p.title', 'fp.user_id as firm_user_id'])
                ->first();

            $title      = $engagement?->title ?? 'your project';
            $firmUserId = $engagement?->firm_user_id;

            // Notify creator
            DB::table('creator_marketplace_notifications')->insert([
                'user_id'    => $payout->creator_id,
                'type'       => 'payout_paid',
                'title'      => 'Payment received!',
                'body'       => '₹' . number_format((float) $payout->net_amount, 2)
                                . " has been transferred to your bank account for \"{$title}\".",
                'data'       => json_encode([
                    'engagement_id'         => (int) $payout->engagement_id,
                    'amount'                => (float) $payout->net_amount,
                    'transaction_reference' => $request->transaction_reference,
                ]),
                'read_at'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Notify firm that the project is now complete
            if ($firmUserId) {
                DB::table('creator_marketplace_notifications')->insert([
                    'user_id'    => $firmUserId,
                    'type'       => 'project_completed',
                    'title'      => 'Project complete!',
                    'body'       => "The creator has been paid and \"{$title}\" is now complete.",
                    'data'       => json_encode(['engagement_id' => (int) $payout->engagement_id]),
                    'read_at'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            AdminActivityLogger::log($admin, AdminActivityLogger::CREATOR_PAYOUT_PAID, 'creator_payout', $id, "Marked creator payout #{$id} as paid.", $request);

            return response()->json(['status' => true, 'message' => 'Payout marked as paid. Project completed.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminPayouts@markPaid: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/creator-payouts/{id}/mark-failed
    // ─────────────────────────────────────────────────────────────────────────

    public function markFailed(Request $request, $id): JsonResponse
    {
        $admin = $this->admin($request);
        if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $validator = Validator::make($request->all(), [
            'admin_notes' => 'required|string|max:1000',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        $payout = DB::table('creator_payouts')->where('id', $id)->first();
        if (! $payout) {
            return response()->json(['status' => false, 'message' => 'Payout not found'], 404);
        }
        if ($payout->status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'Payout is not in pending state'], 422);
        }

        DB::beginTransaction();
        try {
            DB::table('creator_payouts')->where('id', $id)->update([
                'status'       => 'failed',
                'admin_notes'  => $request->admin_notes,
                'processed_by' => $admin->id,
                'updated_at'   => now(),
            ]);

            DB::table('engagement_timeline')->insert([
                'engagement_id' => $payout->engagement_id,
                'user_id'       => null,
                'role'          => 'system',
                'event'         => 'payout_failed',
                'note'          => "Payout failed: {$request->admin_notes}",
                'meta'          => json_encode(['payout_id' => (int) $id]),
                'created_at'    => now(),
            ]);

            $title = DB::table('creator_engagements as ce')
                ->join('creator_projects as p', 'p.id', '=', 'ce.creator_requirement_id')
                ->where('ce.id', $payout->engagement_id)
                ->value('p.title') ?? 'your project';

            DB::table('creator_marketplace_notifications')->insert([
                'user_id'    => $payout->creator_id,
                'type'       => 'payout_failed',
                'title'      => 'Payout failed',
                'body'       => "The payout for \"{$title}\" could not be processed. "
                                . "Please contact support. Ref: {$request->admin_notes}",
                'data'       => json_encode(['engagement_id' => (int) $payout->engagement_id]),
                'read_at'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();

            AdminActivityLogger::log($admin, AdminActivityLogger::CREATOR_PAYOUT_FAILED, 'creator_payout', $id, "Marked creator payout #{$id} as failed.", $request);

            return response()->json(['status' => true, 'message' => 'Payout marked as failed.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminPayouts@markFailed: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/creator-payouts/stats
    // ─────────────────────────────────────────────────────────────────────────

    public function getStats(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        // Escrow held = firm payments verified and project active (funds received, not yet paid out)
        $escrowHeld = (float) DB::table('creator_engagement_payments')
            ->where('status', 'escrow_held')
            ->sum('amount');

        $pendingCount = (int)   DB::table('creator_payouts')->where('status', 'pending')->count();
        $pendingNet   = (float) DB::table('creator_payouts')->where('status', 'pending')->sum('net_amount');
        $pendingGross = (float) DB::table('creator_payouts')->where('status', 'pending')->sum('gross_amount');

        $paidCount    = (int)   DB::table('creator_payouts')->where('status', 'paid')->count();
        $paidNet      = (float) DB::table('creator_payouts')->where('status', 'paid')->sum('net_amount');

        $commissionEarned = (float) DB::table('creator_payouts')
            ->where('status', 'paid')
            ->sum('commission_amount');

        $failedCount = (int)   DB::table('creator_payouts')->where('status', 'failed')->count();
        $failedNet   = (float) DB::table('creator_payouts')->where('status', 'failed')->sum('net_amount');

        $refundsTotal = (float) DB::table('creator_engagement_payments')
            ->where('status', 'refunded')
            ->sum('amount');

        $commissionRate = (float) (DB::table('platform_settings')
            ->where('key', 'commission_percentage')
            ->value('value') ?? '10');

        $approvedUnqueued = (int) DB::table('creator_engagements')
            ->where('status', 'approved')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                  ->from('creator_payouts')
                  ->whereColumn('creator_payouts.engagement_id', 'creator_engagements.id');
            })
            ->count();

        return response()->json([
            'status' => true,
            'data'   => [
                'escrow_held'          => $escrowHeld,
                'pending_count'        => $pendingCount,
                'pending_net'          => $pendingNet,
                'pending_gross'        => $pendingGross,
                'paid_count'           => $paidCount,
                'paid_net'             => $paidNet,
                'commission_earned'    => $commissionEarned,
                'failed_count'         => $failedCount,
                'failed_net'           => $failedNet,
                'refunds_total'        => $refundsTotal,
                'commission_rate'      => $commissionRate,
                'approved_unqueued'    => $approvedUnqueued,
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/creator-payouts/pending-count
    // ─────────────────────────────────────────────────────────────────────────

    public function pendingCount(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $count = (int) DB::table('creator_payouts')->where('status', 'pending')->count();
        return response()->json(['status' => true, 'data' => ['pending_count' => $count]]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/creator-payouts/flush-approved
    // Queue payout records for any 'approved' engagements that slipped through
    // before the immediate-queue logic was in place (e.g. approved via old flow).
    // ─────────────────────────────────────────────────────────────────────────

    public function flushApproved(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        try {
            $commissionRate = (float) (DB::table('platform_settings')
                ->where('key', 'commission_percentage')
                ->value('value') ?? '10');

            $approved = DB::table('creator_engagements')
                ->where('status', 'approved')
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                      ->from('creator_payouts')
                      ->whereColumn('creator_payouts.engagement_id', 'creator_engagements.id');
                })
                ->get(['id', 'creator_id', 'creator_requirement_id', 'accepted_bid_amount']);

            $queued = 0;

            foreach ($approved as $eng) {
                DB::beginTransaction();

                $gross      = (float) $eng->accepted_bid_amount;
                $commission = round($gross * $commissionRate / 100, 2);
                $net        = round($gross - $commission, 2);

                DB::table('creator_payouts')->insert([
                    'engagement_id'     => $eng->id,
                    'creator_id'        => $eng->creator_id,
                    'gross_amount'      => $gross,
                    'commission_rate'   => $commissionRate,
                    'commission_amount' => $commission,
                    'net_amount'        => $net,
                    'status'            => 'pending',
                    'created_at'        => now(),
                    'updated_at'        => now(),
                ]);

                DB::table('creator_engagements')->where('id', $eng->id)->update([
                    'status'     => 'payout_pending',
                    'updated_at' => now(),
                ]);

                DB::table('engagement_timeline')->insert([
                    'engagement_id' => $eng->id,
                    'user_id'       => $admin->id,
                    'role'          => 'system',
                    'event'         => 'payout_queued',
                    'note'          => 'Manually queued by admin',
                    'meta'          => json_encode(['commission_rate' => $commissionRate]),
                    'created_at'    => now(),
                ]);

                $title = DB::table('creator_projects')
                    ->where('id', $eng->creator_requirement_id)
                    ->value('title') ?? 'your project';

                DB::table('creator_marketplace_notifications')->insert([
                    'user_id'    => $eng->creator_id,
                    'type'       => 'payout_queued',
                    'title'      => 'Payout queued',
                    'body'       => "Your payout of ₹" . number_format($net, 2) . " for \"{$title}\" has been queued for processing.",
                    'data'       => json_encode(['engagement_id' => (int) $eng->id]),
                    'read_at'    => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                DB::commit();
                $queued++;
            }

            AdminActivityLogger::log($admin, AdminActivityLogger::CREATOR_PAYOUTS_FLUSHED, 'creator_payout', null, "Flushed/queued creator payouts for approved engagements ({$queued} queued).", $request);

            return response()->json([
                'status'  => true,
                'message' => $queued > 0
                    ? "{$queued} payout(s) queued successfully."
                    : 'No approved engagements pending queue.',
                'data'    => ['queued' => $queued],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('AdminPayouts@flushApproved: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /admin/commission-rate
    // ─────────────────────────────────────────────────────────────────────────

    public function getCommissionRate(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $rate = (float) (DB::table('platform_settings')
            ->where('key', 'commission_percentage')
            ->value('value') ?? '10');

        return response()->json(['status' => true, 'data' => ['commission_percentage' => $rate]]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // POST /admin/commission-rate
    // ─────────────────────────────────────────────────────────────────────────

    public function updateCommissionRate(Request $request): JsonResponse
    {
        $admin = $this->admin($request);
        if (! $admin) return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);

        $validator = Validator::make($request->all(), [
            'commission_percentage' => 'required|numeric|min:0|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        DB::table('platform_settings')
            ->where('key', 'commission_percentage')
            ->update([
                'value'      => (string) $request->commission_percentage,
                'updated_by' => $admin->id,
                'updated_at' => now(),
            ]);

        $rate = $request->commission_percentage;
        AdminActivityLogger::log($admin, AdminActivityLogger::PLATFORM_SETTINGS_UPDATED, 'platform_setting', 'commission_rate', "Updated platform commission rate to {$rate}%.", $request);

        return response()->json(['status' => true, 'message' => 'Commission rate updated.']);
    }
}
