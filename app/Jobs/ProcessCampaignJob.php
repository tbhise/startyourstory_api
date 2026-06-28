<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Services\Campaign\ReEngagementCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs one campaign off the request cycle.
 *
 * The send API creates a pending `campaigns` row and dispatches this job; the worker
 * streams the eligible set in chunks and sends (see ReEngagementCampaignService::run).
 * Not retried (tries=1): a half-sent campaign must not re-send to everyone.
 */
class ProcessCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** Large campaigns can run a while; allow up to an hour on the worker. */
    public int $timeout = 3600;

    public function __construct(public int $campaignId) {}

    public function handle(ReEngagementCampaignService $service): void
    {
        $campaign = Campaign::find($this->campaignId);

        // Only run a fresh, pending campaign — guards against double-dispatch / requeue.
        if (!$campaign || $campaign->status !== Campaign::STATUS_PENDING) {
            return;
        }

        $service->run($campaign);
    }

    public function failed(Throwable $e): void
    {
        Log::error('ProcessCampaignJob failed', ['campaign_id' => $this->campaignId, 'error' => $e->getMessage()]);

        Campaign::where('id', $this->campaignId)
            ->where('status', '!=', Campaign::STATUS_COMPLETED)
            ->update(['status' => Campaign::STATUS_FAILED, 'completed_at' => now()]);
    }
}
