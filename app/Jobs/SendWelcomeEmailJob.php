<?php

namespace App\Jobs;

use App\Mail\WelcomeEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable;

    public function __construct(
        public string $email,
        public string $name,
        public ?string $couponCode = null,
        public string $userType = 'student'

    ) {}

    public function handle(): void
    {
        Mail::to($this->email)
            ->send(
                new WelcomeEmail(
                    $this->name,
                    $this->couponCode,
                    $this->userType
                )
            );
    }
}
