<?php

namespace App\Jobs;

use App\Mail\VerifyEmailMail;
use App\Models\EmailLog;
use App\Models\User;
use App\Services\Email\EmailSenderResolver;
use App\Enums\EmailPurpose;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable;

    public int $tries = 3;
    public array $backoff = [60, 120];

    public function __construct(
        public User $user,
        public int  $emailLogId = 0
    ) {}

    public function handle(): void
    {
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id'   => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );

        $mailable = new VerifyEmailMail($this->user->name, $verificationUrl);

        $sender = EmailSenderResolver::resolve(EmailPurpose::VERIFICATION);
        $mailable->from = [['address' => $sender['address'], 'name' => $sender['name']]];

        Mail::to($this->user->email)->send($mailable);

        if ($this->emailLogId) {
            EmailLog::find($this->emailLogId)?->markSent();
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Verification email job failed', [
            'user_id' => $this->user->id,
            'error'   => $exception->getMessage(),
        ]);

        if ($this->emailLogId) {
            EmailLog::find($this->emailLogId)?->markFailed(
                mb_substr($exception->getMessage(), 0, 500)
            );
        }
    }
}
