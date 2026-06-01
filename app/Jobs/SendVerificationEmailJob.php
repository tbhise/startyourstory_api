<?php

namespace App\Jobs;

use App\Mail\VerifyEmailMail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;
class SendVerificationEmailJob implements ShouldQueue
{
    use Dispatchable;

    public function __construct(
        public User $user
    ) {}

 
    public function handle(): void
    {
        Log::info('Preparing to send verification email to user ID: ' . $this->user->id);
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $this->user->id,
                'hash' => sha1($this->user->email),
            ]
        );

        Mail::to($this->user->email)
            ->send(
                new VerifyEmailMail(
                    $this->user->name,
                    $verificationUrl
                )
            );
        Log::info('Verification email sent to user ID: ' . $this->user->id);
    }
}
