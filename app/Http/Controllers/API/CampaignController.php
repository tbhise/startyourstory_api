<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Services\AdminActivityLogger;
use App\Services\Campaign\ReEngagementCampaignService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Admin Campaign module (re-engagement). Replaces the old public GET trigger in
 * web.php. Every /admin/* path is already guarded by AdminAuthMiddleware, which also
 * exposes the acting admin via $request->attributes->get('admin_user').
 *
 *   POST /admin/campaigns/dry-run  — count eligible users (sends nothing)
 *   POST /admin/campaigns/test     — single preview email (no campaign, no bulk)
 *   POST /admin/campaigns/send     — create campaign + queue it
 *   GET  /admin/campaigns          — execution history
 */
class CampaignController extends Controller
{
    /** Above this eligible count, a real send needs explicit confirmation. */
    private const LARGE_THRESHOLD = 500;
    private const PER_PAGE = 25;

    public function __construct(private ReEngagementCampaignService $service) {}

    /*
    |--------------------------------------------------------------------------
    | POST /admin/campaigns/dry-run
    |--------------------------------------------------------------------------
    */
    public function dryRun(Request $request)
    {
        try {
            $filters = $this->service->normalizeFilters($request->all());
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        try {
            $result = $this->service->dryRun($filters);
            $dup    = $this->service->recentDuplicate($filters);

            return response()->json([
                'status'  => true,
                'message' => 'Eligible recipients calculated',
                'data'    => [
                    'eligible_count'    => $result['eligible_count'],
                    'sample_users'      => $result['sample_users'],
                    'large_campaign'    => $result['eligible_count'] > self::LARGE_THRESHOLD,
                    'large_threshold'   => self::LARGE_THRESHOLD,
                    'duplicate_warning' => (bool) $dup,
                    'last_run_at'       => $dup?->created_at,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('CampaignController@dryRun: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/campaigns/test  — QA preview to a single address
    |--------------------------------------------------------------------------
    */
    public function test(Request $request)
    {
        $email = trim((string) $request->input('email'));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json(['status' => false, 'message' => 'A valid email is required.'], 422);
        }

        try {
            $filters = $this->service->normalizeFilters($request->all());
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        try {
            $this->service->sendTest($email, $filters);
            return response()->json(['status' => true, 'message' => "Test campaign email sent to {$email}."]);
        } catch (\Throwable $e) {
            Log::error('CampaignController@test: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Failed to send test email.'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | POST /admin/campaigns/send  — create + queue
    | Body: target_type, verification_status, profile_completion_status,
    |       campaign_name?, force? (override 24h duplicate), confirm? (>500)
    |--------------------------------------------------------------------------
    */
    public function send(Request $request)
    {
        try {
            $filters = $this->service->normalizeFilters($request->all());
        } catch (InvalidArgumentException $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 422);
        }

        $force   = filter_var($request->input('force'), FILTER_VALIDATE_BOOLEAN);
        $confirm = filter_var($request->input('confirm'), FILTER_VALIDATE_BOOLEAN);

        try {
            // 1. Duplicate guard — block a same-filter run within 24h unless forced.
            $dup = $this->service->recentDuplicate($filters);
            if ($dup && !$force) {
                return response()->json([
                    'status'  => false,
                    'message' => 'A campaign with these exact filters ran in the last 24 hours. Re-send with force=true to override.',
                    'data'    => ['duplicate' => true, 'last_campaign_id' => $dup->id, 'last_run_at' => $dup->created_at],
                ], 409);
            }

            // 2. Server-side eligibility (never trust the client's dry-run number).
            $eligible = $this->service->buildEligibilityQuery($filters)->count();
            if ($eligible === 0) {
                return response()->json(['status' => false, 'message' => 'No eligible recipients for these filters.'], 422);
            }

            // 3. Large-campaign confirmation.
            if ($eligible > self::LARGE_THRESHOLD && !$confirm) {
                return response()->json([
                    'status'  => false,
                    'message' => "Large campaign detected ({$eligible} recipients). Re-send with confirm=true.",
                    'data'    => ['needs_confirmation' => true, 'eligible_count' => $eligible],
                ], 422);
            }

            // 4. Create + queue.
            $admin = $request->attributes->get('admin_user');
            $name  = trim((string) $request->input('campaign_name')) ?: null;

            $campaign = $this->service->createCampaign($filters, Campaign::FROM_ADMIN, $admin->id ?? null, $name);
            ProcessCampaignJob::dispatch($campaign->id);

            // 5. Audit trail (also powers the dashboard "Campaign executed" activity).
            AdminActivityLogger::log(
                $admin,
                AdminActivityLogger::CAMPAIGN_EXECUTED,
                'campaign',
                $campaign->id,
                "Executed re-engagement campaign '{$campaign->campaign_name}' → {$eligible} recipients queued.",
                $request
            );

            return response()->json([
                'status'  => true,
                'message' => "Campaign queued for {$eligible} recipients.",
                'data'    => ['campaign' => $campaign->fresh()],
            ]);
        } catch (\Throwable $e) {
            Log::error('CampaignController@send: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/campaigns  — execution history (latest first)
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        try {
            $page = max(1, (int) $request->get('page', 1));

            $total = DB::table('campaigns')->count();

            $rows = DB::table('campaigns')
                ->leftJoin('admin_users', 'admin_users.id', '=', 'campaigns.executed_by_admin_id')
                ->select([
                    'campaigns.id',
                    'campaigns.campaign_type',
                    'campaigns.campaign_name',
                    'campaigns.target_type',
                    'campaigns.verification_status',
                    'campaigns.profile_completion_status',
                    'campaigns.eligible_count',
                    'campaigns.sent_count',
                    'campaigns.failed_count',
                    'campaigns.opened_count',
                    'campaigns.clicked_count',
                    'campaigns.status',
                    'campaigns.initiated_from',
                    'campaigns.started_at',
                    'campaigns.completed_at',
                    'campaigns.created_at',
                    'admin_users.name as executed_by',
                ])
                ->orderByDesc('campaigns.id')
                ->offset(($page - 1) * self::PER_PAGE)
                ->limit(self::PER_PAGE)
                ->get();

            return response()->json([
                'status'  => true,
                'message' => 'Campaigns fetched',
                'data'    => [
                    'campaigns' => $rows,
                    'total'     => $total,
                    'page'      => $page,
                    'has_more'  => ($page * self::PER_PAGE) < $total,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('CampaignController@index: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GET /admin/campaigns/stats  — header KPI counts by status
    |--------------------------------------------------------------------------
    */
    public function stats()
    {
        try {
            $base = DB::table('campaigns');

            return response()->json([
                'status'  => true,
                'message' => 'Campaign stats',
                'data'    => [
                    'total'     => (clone $base)->count(),
                    'pending'   => (clone $base)->where('status', 'pending')->count(),
                    'running'   => (clone $base)->where('status', 'running')->count(),
                    'completed' => (clone $base)->where('status', 'completed')->count(),
                    'failed'    => (clone $base)->where('status', 'failed')->count(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('CampaignController@stats: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Server error'], 500);
        }
    }
}
