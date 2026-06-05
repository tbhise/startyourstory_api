<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class WelcomeEmail extends Mailable implements HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $name,
        public ?string $couponCode = null,
        public string $userType = 'student'
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::WELCOME;
    }

    public function build()
    {
        return $this->subject('Welcome to Start Your Story')
            ->view('emails.welcome');
    }
}
