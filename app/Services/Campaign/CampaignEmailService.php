<?php

namespace App\Services\Campaign;

use App\Services\Notifications\EmailNotificationService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Bulk campaign queueing engine.
 *
 * Iterates a recipient set (from CampaignRecipientService) and queues one email
 * per recipient through EmailNotificationService — so every campaign email gets
 * the standard pipeline: email_logs row + DispatchMailJob (sender identity by
 * purpose, delivery, markSent/markFailed). NO direct Mail::send here.
 *
 * Per-recipient safety: invalid addresses are skipped, exceptions are caught,
 * logged and counted — one bad recipient never aborts the run.
 *
 * Adding a future campaign = one entry in queueFnFor() plus its wrapper on
 * EmailNotificationService. Commands stay thin: they parse options, pick a
 * campaign key, and print whatever the progress callback reports.
 */
class CampaignEmailService
{
    public const STUDENT_FEATURE_RELEASE   = 'student-feature-release';
    public const FIRM_REENGAGEMENT_FEATURE = 'firm-reengagement-feature';

    public function __construct(
        private readonly EmailNotificationService $mailer,
    ) {}

    /**
     * Queue a campaign for every recipient in the set.
     *
     * @param  iterable<object{id:?int,name:?string,email:string}> $recipients
     * @param  callable(string $status, object $recipient, string $note):void|null $progress
     * @return array{found:int,queued:int,skipped:int,failed:int}
     */
    public function send(string $campaign, iterable $recipients, ?callable $progress = null): array
    {
        $queueFn = $this->queueFnFor($campaign);
        $stats   = ['found' => 0, 'queued' => 0, 'skipped' => 0, 'failed' => 0];

        foreach ($recipients as $recipient) {
            $stats['found']++;

            if (empty($recipient->email) || !filter_var($recipient->email, FILTER_VALIDATE_EMAIL)) {
                $stats['skipped']++;
                if ($progress) $progress('skipped', $recipient, 'invalid email');
                continue;
            }

            try {
                $logId = $queueFn($recipient);
                $stats['queued']++;
                if ($progress) $progress('queued', $recipient, "email_log #{$logId}");
            } catch (Throwable $e) {
                $stats['failed']++;
                Log::warning('Campaign queue failed for recipient', [
                    'campaign' => $campaign,
                    'email'    => $recipient->email,
                    'error'    => $e->getMessage(),
                ]);
                if ($progress) $progress('failed', $recipient, mb_substr($e->getMessage(), 0, 120));
            }
        }

        return $stats;
    }

    /**
     * Map a campaign key to the EmailNotificationService wrapper that queues it.
     */
    private function queueFnFor(string $campaign): \Closure
    {
        return match ($campaign) {
            self::STUDENT_FEATURE_RELEASE => fn (object $r): int =>
                $this->mailer->sendStudentFeatureReleaseEmail($r->email, (string) ($r->name ?? '')),
            self::FIRM_REENGAGEMENT_FEATURE => fn (object $r): int =>
                $this->mailer->sendFirmFeatureReleaseEmail($r->email, (string) ($r->name ?? '')),
            default => throw new InvalidArgumentException("Unknown campaign '{$campaign}'."),
        };
    }
}
