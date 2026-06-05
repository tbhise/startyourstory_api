<?php

namespace App\Jobs;

use App\Contracts\Mail\HasEmailPurpose;
use App\Models\EmailLog;
use App\Services\Email\EmailSenderResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Bus\Dispatchable;
use Throwable;

/**
 * Generic queued mail dispatcher.
 *
 * All one-off transactional emails (interview notifications, firm approval, etc.)
 * are sent through this job. It resolves the correct sender identity, delivers
 * the mail, and updates the email_logs record.
 *
 * Complex emails with pre-send logic (verification URL generation, digests)
 * have their own dedicated job classes.
 */
class DispatchMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public array $backoff = [60, 180];

    public function __construct(
        public readonly string   $recipientEmail,
        public readonly Mailable $mailable,
        public readonly int      $emailLogId
    ) {}

    public function handle(): void
    {
        if ($this->mailable instanceof HasEmailPurpose) {
            $sender = EmailSenderResolver::resolve($this->mailable->emailPurpose());
            $this->mailable->from = [['address' => $sender['address'], 'name' => $sender['name']]];
        }

        Mail::to($this->recipientEmail)->send($this->mailable);

        EmailLog::find($this->emailLogId)?->markSent();
    }

    public function failed(Throwable $exception): void
    {
        EmailLog::find($this->emailLogId)?->markFailed(
            mb_substr($exception->getMessage(), 0, 500)
        );
    }
}
