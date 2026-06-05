<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class VerifyEmailMail extends Mailable implements ShouldQueue, HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $name,
        public string $verificationUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::VERIFICATION;
    }

    public function build()
    {
        return $this
            ->subject('Verify Your Email Address')
            ->view('emails.verify-email');
    }
}
