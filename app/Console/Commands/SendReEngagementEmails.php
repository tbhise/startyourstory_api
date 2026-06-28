<?php

namespace App\Console\Commands;

use App\Jobs\ProcessCampaignJob;
use App\Models\Campaign;
use App\Services\AdminActivityLogger;
use App\Services\Campaign\ReEngagementCampaignService;
use Illuminate\Console\Command;
use InvalidArgumentException;

/**
 * Re-engagement campaign — CLI entry point.
 *
 * Thin wrapper around ReEngagementCampaignService (the same engine the admin
 * Campaign API uses). Creates a `campaigns` row per target type and, by default,
 * dispatches it to the queue (ProcessCampaignJob). Pass --sync to run inline.
 *
 *   php artisan mail:reengagement                       # ALL types, queued
 *   php artisan mail:reengagement --sync                # ALL types, inline
 *   php artisan mail:reengagement --type=firm           # one type
 *   php artisan mail:reengagement --profile=0           # incomplete profiles only
 *   php artisan mail:reengagement --dry-run             # count only, sends nothing
 *
 * The legacy public GET trigger (web.php /admin/send-reengagement) has been removed;
 * campaigns run only via this command or the admin API.
 */
class SendReEngagementEmails extends Command
{
    protected $signature = 'mail:reengagement
        {--type=     : Restrict to a single target type: student|creator|firm (default: all three)}
        {--verified= : Verification state: 0 (unverified) or 1 (verified). Omit for all}
        {--profile=  : Profile state: 0 (incomplete) or 1 (completed). Omit for all}
        {--dry-run   : Count eligible recipients without sending}
        {--sync      : Run inline instead of dispatching to the queue}';

    protected $description = 'Run the re-engagement campaign (per target type) via the shared campaign service. Queued by default; --sync to run inline.';

    private const TYPES = ['student', 'creator', 'firm'];

    public function handle(ReEngagementCampaignService $service): int
    {
        $type     = $this->option('type');
        $verified = $this->option('verified');
        $profile  = $this->option('profile');
        $dryRun   = (bool) $this->option('dry-run');
        $sync     = (bool) $this->option('sync');

        // ── Validate + map options onto the service's filter contract ──────────
        if ($type !== null && !in_array($type, self::TYPES, true)) {
            $this->error('Invalid --type. Allowed: ' . implode(', ', self::TYPES) . '.');
            return self::FAILURE;
        }
        if ($verified !== null && !in_array($verified, ['0', '1'], true)) {
            $this->error('Invalid --verified. Use 0 (unverified) or 1 (verified).');
            return self::FAILURE;
        }
        if ($profile !== null && !in_array($profile, ['0', '1'], true)) {
            $this->error('Invalid --profile. Use 0 (incomplete) or 1 (completed).');
            return self::FAILURE;
        }

        $verification = $verified === null ? 'all' : ($verified === '1' ? 'verified' : 'unverified');
        $profileState = $profile === null ? 'all' : ($profile === '1' ? 'completed' : 'incomplete');
        $targets      = $type ? [$type] : self::TYPES;

        foreach ($targets as $target) {
            $filters = [
                'target_type'               => $target,
                'verification_status'        => $verification,
                'profile_completion_status'  => $profileState,
            ];

            try {
                if ($dryRun) {
                    $res = $service->dryRun($filters);
                    $this->info("[{$target}] eligible: {$res['eligible_count']}");
                    continue;
                }

                $campaign = $service->createCampaign($filters, Campaign::FROM_CLI, null, null);

                if ($sync) {
                    $service->run($campaign);
                    $campaign->refresh();
                    $this->info("[{$target}] sent: {$campaign->sent_count}  failed: {$campaign->failed_count}  (campaign #{$campaign->id})");
                } else {
                    ProcessCampaignJob::dispatch($campaign->id);
                    $this->info("[{$target}] queued campaign #{$campaign->id} ({$campaign->eligible_count} recipients). Run a queue worker to process it.");
                }

                // Audit trail (admin null = CLI-initiated).
                AdminActivityLogger::log(
                    null,
                    AdminActivityLogger::CAMPAIGN_EXECUTED,
                    'campaign',
                    $campaign->id,
                    "Re-engagement campaign '{$campaign->campaign_name}' "
                        . ($sync ? 'sent' : 'queued') . " via CLI → {$campaign->eligible_count} recipients."
                );
            } catch (InvalidArgumentException $e) {
                $this->error("[{$target}] {$e->getMessage()}");
                return self::FAILURE;
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
