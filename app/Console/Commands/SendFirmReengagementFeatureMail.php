<?php

namespace App\Console\Commands;

use App\Services\Campaign\CampaignEmailService;

/**
 * Firm re-engagement + feature release campaign.
 *
 *   php artisan mail:firm-reengagement-feature --dry-run
 *   php artisan mail:firm-reengagement-feature --limit=10
 *   php artisan mail:firm-reengagement-feature --email=someone@example.com
 *   php artisan mail:firm-reengagement-feature --force   (full audience, no prompt)
 */
class SendFirmReengagementFeatureMail extends AbstractFeatureReleaseCampaignCommand
{
    protected $signature = 'mail:firm-reengagement-feature
                            {--dry-run : Show recipient count + samples, queue nothing}
                            {--limit= : Queue for the first N firms only}
                            {--email= : Queue for a single email address only}
                            {--force : Skip the full-audience confirmation prompt}';

    protected $description = 'Queue the firm re-engagement + feature release campaign email (template: firm-reengagement-feature)';

    protected function role(): string
    {
        return 'firm';
    }

    protected function campaign(): string
    {
        return CampaignEmailService::FIRM_REENGAGEMENT_FEATURE;
    }

    protected function audienceLabel(): string
    {
        return 'firms';
    }
}
