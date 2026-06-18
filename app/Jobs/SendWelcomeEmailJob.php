<?php

namespace App\Jobs;

use App\Mail\WelcomeEmail;
use App\Models\EmailLog;
use App\Services\Email\EmailSenderResolver;
use App\Enums\EmailPurpose;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries  = 5;
    public array $backoff = [60, 120, 300];

    public function __construct(
        public string  $email,
        public string  $name,
        public ?string $couponCode = null,
        public string  $userType   = 'student',
        public int     $emailLogId = 0
    ) {}

    public function handle(): void
    {
        $mailable = new WelcomeEmail($this->name, $this->couponCode, $this->userType);

        $sender = EmailSenderResolver::resolve(EmailPurpose::WELCOME);
        $mailable->from = [['address' => $sender['address'], 'name' => $sender['name']]];

        Mail::to($this->email)->send($mailable);

        if ($this->emailLogId) {
            EmailLog::find($this->emailLogId)?->markSent();
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Welcome email job failed', [
            'email'    => $this->email,
            'userType' => $this->userType,
            'error'    => $exception->getMessage(),
        ]);

        if ($this->emailLogId) {
            EmailLog::find($this->emailLogId)?->markFailed(
                mb_substr($exception->getMessage(), 0, 500)
            );
        }
    }
}
