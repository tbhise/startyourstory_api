<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Helpers\SubscriptionHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FreeContentController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // Helper — ensure credits row exists for a premium firm
    // ─────────────────────────────────────────────────────────────────────────

    private function ensureCredits(int $firmId): object
    {
        $row = DB::table('firm_content_credits')->where('firm_id', $firmId)->first();
        if (! $row) {
            DB::table('firm_content_credits')->insert([
                'firm_id'       => $firmId,
                'total_credits' => 3,
                'used_credits'  => 0,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $row = DB::table('firm_content_credits')->where('firm_id', $firmId)->first();
        }
        return $row;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Get credit balance
    // ─────────────────────────────────────────────────────────────────────────

    public function getCredits(Request $request): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            if (! SubscriptionHelper::isPremiumFirm($firmProfile->id)) {
                return response()->json(['status' => false, 'message' => 'Premium subscription required', 'code' => 'NOT_PREMIUM'], 403);
            }

            $credits = $this->ensureCredits($firmProfile->id);

            return response()->json([
                'status' => true,
                'data'   => [
                    'total_credits'     => (int) $credits->total_credits,
                    'used_credits'      => (int) $credits->used_credits,
                    'remaining_credits' => max(0, (int) $credits->total_credits - (int) $credits->used_credits),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('FreeContent getCredits error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — Submit a free content request
    // ─────────────────────────────────────────────────────────────────────────

    public function submitRequest(Request $request): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            if (! SubscriptionHelper::isPremiumFirm($firmProfile->id)) {
                return response()->json(['status' => false, 'message' => 'Premium subscription required', 'code' => 'NOT_PREMIUM'], 403);
            }

            $credits = $this->ensureCredits($firmProfile->id);
            $remaining = (int) $credits->total_credits - (int) $credits->used_credits;

            if ($remaining <= 0) {
                return response()->json([
                    'status'  => false,
                    'message' => 'No free content credits remaining.',
                    'code'    => 'NO_CREDITS',
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'brief'         => 'required|string|max:5000',
                'delivery_date' => 'nullable|date|after_or_equal:today',
                'notes'         => 'nullable|string|max:2000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Validation failed',
                    'errors'  => $validator->errors(),
                ], 422);
            }

            // Handle attachments (uploaded files)
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('free-content-attachments', 'public');
                    $attachments[] = [
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                    ];
                }
            }

            $id = DB::table('free_content_requests')->insertGetId([
                'firm_id'       => $firmProfile->id,
                'brief'         => $request->input('brief'),
                'delivery_date' => $request->input('delivery_date'),
                'notes'         => $request->input('notes'),
                'attachments'   => count($attachments) ? json_encode($attachments) : null,
                'status'        => 'pending',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Free content request submitted successfully.',
                'data'    => ['id' => $id],
            ]);
        } catch (\Exception $e) {
            Log::error('FreeContent submitRequest error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // FIRM — List own requests
    // ─────────────────────────────────────────────────────────────────────────

    public function getMyRequests(Request $request): JsonResponse
    {
        try {
            $user        = $request->attributes->get('auth_user');
            $firmProfile = DB::table('firm_profiles')->where('user_id', $user->id)->first();

            if (! $firmProfile) {
                return response()->json(['status' => false, 'message' => 'Firm profile not found'], 404);
            }

            $requests = DB::table('free_content_requests')
                ->where('firm_id', $firmProfile->id)
                ->orderByDesc('created_at')
                ->get();

            $result = $requests->map(function ($r) {
                $deliverables = DB::table('free_content_deliverables')
                    ->where('request_id', $r->id)
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn($d) => [
                        'id'        => $d->id,
                        'file_name' => $d->file_name,
                        'file_url'  => Storage::url($d->file_path),
                    ]);

                return [
                    'id'            => $r->id,
                    'brief'         => $r->brief,
                    'delivery_date' => $r->delivery_date,
                    'notes'         => $r->notes,
                    'attachments'   => $r->attachments ? json_decode($r->attachments, true) : [],
                    'status'        => $r->status,
                    'admin_notes'   => $r->admin_notes,
                    'deliverables'  => $deliverables,
                    'created_at'    => $r->created_at,
                ];
            });

            return response()->json(['status' => true, 'data' => $result]);
        } catch (\Exception $e) {
            Log::error('FreeContent getMyRequests error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — List all requests
    // ─────────────────────────────────────────────────────────────────────────

    public function getAdminRequests(Request $request): JsonResponse
    {
        try {
            $status = $request->query('status');
            $perPage = min((int) ($request->query('per_page', 20)), 100);
            $page    = max((int) ($request->query('page', 1)), 1);

            $query = DB::table('free_content_requests')
                ->join('firm_profiles', 'free_content_requests.firm_id', '=', 'firm_profiles.id')
                ->select(
                    'free_content_requests.*',
                    'firm_profiles.firm_name',
                    'firm_profiles.city as firm_city'
                );

            if ($status) {
                $query->where('free_content_requests.status', $status);
            }

            $total = $query->count();
            $items = $query->orderByDesc('free_content_requests.created_at')
                ->offset(($page - 1) * $perPage)
                ->limit($perPage)
                ->get();

            $result = $items->map(function ($r) {
                $deliverables = DB::table('free_content_deliverables')
                    ->where('request_id', $r->id)
                    ->orderByDesc('created_at')
                    ->get()
                    ->map(fn($d) => [
                        'id'        => $d->id,
                        'file_name' => $d->file_name,
                        'file_url'  => Storage::url($d->file_path),
                    ]);

                return [
                    'id'            => $r->id,
                    'firm_id'       => $r->firm_id,
                    'firm_name'     => $r->firm_name,
                    'firm_city'     => $r->firm_city,
                    'brief'         => $r->brief,
                    'delivery_date' => $r->delivery_date,
                    'notes'         => $r->notes,
                    'attachments'   => $r->attachments ? json_decode($r->attachments, true) : [],
                    'status'        => $r->status,
                    'admin_notes'   => $r->admin_notes,
                    'deliverables'  => $deliverables,
                    'created_at'    => $r->created_at,
                ];
            });

            return response()->json([
                'status' => true,
                'data'   => [
                    'items'    => $result,
                    'total'    => $total,
                    'page'     => $page,
                    'per_page' => $perPage,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('FreeContent getAdminRequests error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Confirm request (deducts credit)
    // ─────────────────────────────────────────────────────────────────────────

    public function confirmRequest(int $id, Request $request): JsonResponse
    {
        try {
            $req = DB::table('free_content_requests')->where('id', $id)->first();
            if (! $req) {
                return response()->json(['status' => false, 'message' => 'Request not found'], 404);
            }
            if ($req->status !== 'pending') {
                return response()->json(['status' => false, 'message' => 'Only pending requests can be confirmed'], 422);
            }

            DB::beginTransaction();

            // Deduct credit
            DB::table('firm_content_credits')
                ->where('firm_id', $req->firm_id)
                ->increment('used_credits');

            DB::table('free_content_requests')
                ->where('id', $id)
                ->update([
                    'status'     => 'confirmed',
                    'admin_notes'=> $request->input('admin_notes'),
                    'updated_at' => now(),
                ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Request confirmed and credit deducted.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FreeContent confirmRequest error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Update status (confirmed → in_progress / delivered)
    // ─────────────────────────────────────────────────────────────────────────

    public function updateStatus(int $id, Request $request): JsonResponse
    {
        try {
            $req = DB::table('free_content_requests')->where('id', $id)->first();
            if (! $req) {
                return response()->json(['status' => false, 'message' => 'Request not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:in_progress,delivered',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'Invalid status', 'errors' => $validator->errors()], 422);
            }

            DB::table('free_content_requests')
                ->where('id', $id)
                ->update([
                    'status'      => $request->input('status'),
                    'admin_notes' => $request->input('admin_notes', $req->admin_notes),
                    'updated_at'  => now(),
                ]);

            return response()->json(['status' => true, 'message' => 'Status updated.']);
        } catch (\Exception $e) {
            Log::error('FreeContent updateStatus error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Upload deliverable file
    // ─────────────────────────────────────────────────────────────────────────

    public function uploadDeliverable(int $id, Request $request): JsonResponse
    {
        try {
            $req = DB::table('free_content_requests')->where('id', $id)->first();
            if (! $req) {
                return response()->json(['status' => false, 'message' => 'Request not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:51200',
            ]);
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => 'File required', 'errors' => $validator->errors()], 422);
            }

            $file     = $request->file('file');
            $path     = $file->store('free-content-deliverables', 'public');
            $fileName = $file->getClientOriginalName();

            $adminUser = $request->attributes->get('admin_user');

            $deliverableId = DB::table('free_content_deliverables')->insertGetId([
                'request_id'  => $id,
                'file_path'   => $path,
                'file_name'   => $fileName,
                'uploaded_by' => $adminUser?->id,
                'created_at'  => now(),
            ]);

            return response()->json([
                'status'  => true,
                'message' => 'Deliverable uploaded.',
                'data'    => [
                    'id'        => $deliverableId,
                    'file_name' => $fileName,
                    'file_url'  => Storage::url($path),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('FreeContent uploadDeliverable error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ADMIN — Reject request
    // ─────────────────────────────────────────────────────────────────────────

    public function rejectRequest(int $id, Request $request): JsonResponse
    {
        try {
            $req = DB::table('free_content_requests')->where('id', $id)->first();
            if (! $req) {
                return response()->json(['status' => false, 'message' => 'Request not found'], 404);
            }
            if (! in_array($req->status, ['pending', 'confirmed'])) {
                return response()->json(['status' => false, 'message' => 'Cannot reject a request in current status'], 422);
            }

            DB::beginTransaction();

            // If confirmed (credit already deducted), refund it
            if ($req->status === 'confirmed') {
                DB::table('firm_content_credits')
                    ->where('firm_id', $req->firm_id)
                    ->where('used_credits', '>', 0)
                    ->decrement('used_credits');
            }

            DB::table('free_content_requests')
                ->where('id', $id)
                ->update([
                    'status'      => 'rejected',
                    'admin_notes' => $request->input('admin_notes'),
                    'updated_at'  => now(),
                ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'Request rejected.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('FreeContent rejectRequest error', ['msg' => $e->getMessage()]);
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
