<?php

namespace App\Http\Controllers\API;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Services\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 * Admin CRUD for Engagement Hub → In-App Campaigns.
 *
 * Type / trigger / frequency are stored as strings (extensible): adding a new
 * value later is a one-line validation change plus, for a new button action or
 * campaign type with bespoke rendering, one frontend addition — existing
 * campaigns need no redeploy. Auth is enforced globally by AdminAuthMiddleware;
 * the local requireAdmin is defense-in-depth, matching other admin controllers.
 */
class AdminInAppCampaignController extends Controller
{
    private const TYPES       = ['notification', 'pwa', 'messages', 'feature_announcement'];
    private const TRIGGERS    = ['dashboard_login', 'interview_confirmed', 'manual'];
    private const FREQUENCIES = ['one_time', 'every_login', 'cooldown', 'never_again'];
    private const STATUSES    = ['draft', 'active', 'paused', 'archived'];
    private const ACTIONS     = ['route', 'url', 'enable_push', 'install_pwa', 'dismiss'];
    private const IMG_DIR     = 'in-app-campaigns';

    private function requireAdmin(Request $request)
    {
        $token = $request->cookie('admin_token');
        $admin = $token
            ? DB::table('admin_users')->where('api_token', $token)->where('is_active', true)->first()
            : null;
        if (!$admin) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        return $admin;
    }

    public function index(Request $request)
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) return $admin;

        try {
            $q = DB::table('in_app_campaigns');
            if ($request->filled('status')) $q->where('status', $request->input('status'));
            if ($request->filled('type'))   $q->where('type', $request->input('type'));
            if ($request->filled('search')) $q->where('title', 'like', '%' . $request->input('search') . '%');

            $rows = $q->orderByDesc('id')->paginate(min((int) ($request->input('per_page', 15)), 50));
            $rows->getCollection()->transform(fn ($r) => $this->present($r));

            return response()->json(['status' => true, 'data' => $rows]);
        } catch (\Throwable $e) {
            Log::error('AdminInAppCampaign@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function show(Request $request, $id)
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) return $admin;

        $row = DB::table('in_app_campaigns')->where('id', $id)->first();
        if (!$row) return response()->json(['status' => false, 'message' => 'Campaign not found'], 404);
        return response()->json(['status' => true, 'data' => $this->present($row)]);
    }

    public function store(Request $request)
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) return $admin;

        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $image = $request->hasFile('image')
                ? ImageHelper::optimizeToWebp($request->file('image'), self::IMG_DIR)
                : null;

            $id = DB::table('in_app_campaigns')->insertGetId(array_merge(
                $this->payload($request),
                ['image' => $image, 'created_by_admin_id' => $admin->id ?? null, 'created_at' => now(), 'updated_at' => now()],
            ));

            AdminActivityLogger::log($admin, 'in_app_campaign_created', 'in_app_campaign', $id, "Created in-app campaign '{$request->input('title')}' (#{$id}).", $request);
            return response()->json(['status' => true, 'message' => 'Campaign created.', 'data' => ['id' => $id]]);
        } catch (\Throwable $e) {
            Log::error('AdminInAppCampaign@store: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) return $admin;

        $existing = DB::table('in_app_campaigns')->where('id', $id)->first();
        if (!$existing) return response()->json(['status' => false, 'message' => 'Campaign not found'], 404);

        $validator = Validator::make($request->all(), $this->rules());
        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()->first()], 422);
        }

        try {
            $data = array_merge($this->payload($request), ['updated_at' => now()]);

            if ($request->hasFile('image')) {
                if ($existing->image) Storage::disk('public')->delete($existing->image);
                $data['image'] = ImageHelper::optimizeToWebp($request->file('image'), self::IMG_DIR);
            } elseif ($request->boolean('remove_image')) {
                if ($existing->image) Storage::disk('public')->delete($existing->image);
                $data['image'] = null;
            }

            DB::table('in_app_campaigns')->where('id', $id)->update($data);
            AdminActivityLogger::log($admin, 'in_app_campaign_updated', 'in_app_campaign', $id, "Updated in-app campaign #{$id}.", $request);
            return response()->json(['status' => true, 'message' => 'Campaign updated.']);
        } catch (\Throwable $e) {
            Log::error('AdminInAppCampaign@update: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    public function destroy(Request $request, $id)
    {
        $admin = $this->requireAdmin($request);
        if ($admin instanceof JsonResponse) return $admin;

        $existing = DB::table('in_app_campaigns')->where('id', $id)->first();
        if (!$existing) return response()->json(['status' => false, 'message' => 'Campaign not found'], 404);

        try {
            if ($existing->image) Storage::disk('public')->delete($existing->image);
            DB::table('in_app_campaign_events')->where('campaign_id', $id)->delete();
            DB::table('in_app_campaigns')->where('id', $id)->delete();
            AdminActivityLogger::log($admin, 'in_app_campaign_deleted', 'in_app_campaign', $id, "Deleted in-app campaign #{$id}.", $request);
            return response()->json(['status' => true, 'message' => 'Campaign deleted.']);
        } catch (\Throwable $e) {
            Log::error('AdminInAppCampaign@destroy: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /* ---------------------------------------------------------------- */

    private function rules(): array
    {
        return [
            'title'                        => 'required|string|max:200',
            'subtitle'                     => 'nullable|string|max:500',
            'image'                        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'primary_btn_label'            => 'nullable|string|max:100',
            'primary_btn_action'           => 'nullable|in:' . implode(',', self::ACTIONS),
            'primary_btn_value'            => 'nullable|string|max:500',
            'secondary_btn_label'          => 'nullable|string|max:100',
            'secondary_btn_action'         => 'nullable|in:' . implode(',', self::ACTIONS),
            'secondary_btn_value'          => 'nullable|string|max:500',
            'type'                         => 'required|in:' . implode(',', self::TYPES),
            'trigger_type'                 => 'required|in:' . implode(',', self::TRIGGERS),
            'priority'                     => 'nullable|integer|min:0|max:100000',
            'frequency'                    => 'required|in:' . implode(',', self::FREQUENCIES),
            'cooldown_hours'               => 'nullable|integer|min:1|max:8760',
            'starts_at'                    => 'nullable|date',
            'ends_at'                      => 'nullable|date|after_or_equal:starts_at',
            'status'                       => 'required|in:' . implode(',', self::STATUSES),
            'audience_target_type'         => 'required|in:all,student,creator,firm',
            'audience_verification_status' => 'required|in:all,verified,unverified',
            'audience_profile_status'      => 'required|in:all,completed,incomplete',
            'audience_plan'                => 'required|in:all,premium,free',
        ];
    }

    /** Column payload shared by store + update (image handled separately). */
    private function payload(Request $request): array
    {
        return [
            'title'                => trim((string) $request->input('title')),
            'subtitle'             => $request->input('subtitle') ?: null,
            'primary_btn_label'    => $request->input('primary_btn_label') ?: null,
            'primary_btn_action'   => $request->input('primary_btn_action') ?: null,
            'primary_btn_value'    => $request->input('primary_btn_value') ?: null,
            'secondary_btn_label'  => $request->input('secondary_btn_label') ?: null,
            'secondary_btn_action' => $request->input('secondary_btn_action') ?: null,
            'secondary_btn_value'  => $request->input('secondary_btn_value') ?: null,
            'type'                 => $request->input('type'),
            'trigger_type'         => $request->input('trigger_type'),
            'priority'             => (int) $request->input('priority', 0),
            'frequency'            => $request->input('frequency'),
            'cooldown_hours'       => $request->input('frequency') === 'cooldown'
                ? (int) $request->input('cooldown_hours', 24)
                : null,
            'starts_at'            => $request->input('starts_at') ?: null,
            'ends_at'              => $request->input('ends_at') ?: null,
            'status'               => $request->input('status'),
            'audience'             => json_encode([
                'target_type'               => $request->input('audience_target_type'),
                'verification_status'        => $request->input('audience_verification_status'),
                'profile_completion_status'  => $request->input('audience_profile_status'),
                'plan'                       => $request->input('audience_plan'),
            ]),
        ];
    }

    /** Shape a DB row for the admin UI (decoded audience + image url + counts). */
    private function present(object $r): array
    {
        $audience = json_decode((string) $r->audience, true) ?: [];
        return [
            'id'                   => (string) $r->id,
            'title'                => $r->title,
            'subtitle'             => $r->subtitle,
            'image'                => $r->image,
            'image_url'            => $r->image ? asset('storage/' . $r->image) : null,
            'primary_btn_label'    => $r->primary_btn_label,
            'primary_btn_action'   => $r->primary_btn_action,
            'primary_btn_value'    => $r->primary_btn_value,
            'secondary_btn_label'  => $r->secondary_btn_label,
            'secondary_btn_action' => $r->secondary_btn_action,
            'secondary_btn_value'  => $r->secondary_btn_value,
            'type'                 => $r->type,
            'trigger_type'         => $r->trigger_type,
            'priority'             => (int) $r->priority,
            'frequency'            => $r->frequency,
            'cooldown_hours'       => $r->cooldown_hours !== null ? (int) $r->cooldown_hours : null,
            'starts_at'            => $r->starts_at,
            'ends_at'              => $r->ends_at,
            'status'               => $r->status,
            'audience_target_type'         => $audience['target_type'] ?? 'all',
            'audience_verification_status' => $audience['verification_status'] ?? 'all',
            'audience_profile_status'      => $audience['profile_completion_status'] ?? 'all',
            'audience_plan'                => $audience['plan'] ?? 'all',
            'shown_count'   => (int) DB::table('in_app_campaign_events')->where('campaign_id', $r->id)->where('action', 'shown')->count(),
            'clicked_count' => (int) DB::table('in_app_campaign_events')->where('campaign_id', $r->id)->whereIn('action', ['clicked_primary', 'clicked_secondary'])->count(),
            'created_at'    => $r->created_at,
        ];
    }
}
