<?php

namespace App\Console\Commands;

use App\Services\Campaign\CampaignEmailService;

/**
 * Student re-engagement + feature release campaign.
 *
 *   php artisan mail:student-reengagement-feature --dry-run
 *   php artisan mail:student-reengagement-feature --limit=10
 *   php artisan mail:student-reengagement-feature --email=someone@example.com
 *   php artisan mail:student-reengagement-feature --force   (full audience, no prompt)
 */
class SendStudentReengagementFeatureMail extends AbstractFeatureReleaseCampaignCommand
{
    protected $signature = 'mail:student-reengagement-feature
                            {--dry-run : Show recipient count + samples, queue nothing}
                            {--limit= : Queue for the first N students only}
                            {--email= : Queue for a single email address only}
                            {--force : Skip the full-audience confirmation prompt}';

    protected $description = 'Queue the student re-engagement + feature release campaign email (template: student-feature-release)';

    protected function role(): string
    {
        return 'student';
    }

    protected function campaign(): string
    {
        return CampaignEmailService::STUDENT_FEATURE_RELEASE;
    }

    protected function audienceLabel(): string
    {
        return 'students';
    }
}
