<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

class WelcomeEmail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $name,
        public ?string $couponCode = null,
        public string $userType = 'student'
    ) {}

    public function build()
    {
        return $this->subject('Welcome to Start Your Story')
            ->view('emails.welcome');
    }
}
