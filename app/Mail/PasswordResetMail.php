<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class PasswordResetMail extends Mailable implements HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $resetUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::PASSWORD_RESET;
    }

    public function build()
    {
        return $this->subject('Reset Your Password — Start Your Story')
            ->view('emails.password-reset');
    }
}
