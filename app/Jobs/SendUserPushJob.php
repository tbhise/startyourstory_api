<?php

namespace App\Jobs;

use App\Services\Notifications\UserPushService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Queued push delivery for STUDENT / FIRM devices.
 *
 * Push is an ADDITIVE notification layer: trigger points dispatch this job
 * right next to their existing NotificationHelper / email calls, so the HTTP
 * request (or scheduled job) never waits on FCM. UserPushService is itself
 * non-throwing and a no-op when FCM is unconfigured, so this job can never
 * fail a business flow — worst case a push is silently skipped and the
 * existing in-app + email notifications still deliver.
 *
 * Admin push (FcmService::sendToAllAdmins) is intentionally NOT routed
 * through this job — the admin pipeline is untouched.
 */
class SendUserPushJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(
        public int $userId,
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public array $data = [],
        public ?string $collapseTag = null
    ) {
    }

    public function handle(): void
    {
        try {
            UserPushService::sendToUser(
                $this->userId,
                $this->title,
                $this->body,
                $this->actionUrl,
                $this->data,
                $this->collapseTag
            );
        } catch (Throwable $e) {
            // Belt-and-braces: never let a push failure surface as a failed job.
            Log::error('SendUserPushJob failed', [
                'user_id' => $this->userId,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}
