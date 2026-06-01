<?php

namespace App\Jobs;

use App\Mail\WelcomeEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Throwable;

// class SendWelcomeEmailJob implements ShouldQueue
// {
//      use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

//     public function __construct(
//         public string $email,
//         public string $name,
//         public ?string $couponCode = null,
//         public string $userType = 'student'

//     ) {}

//     public $tries = 5;
//     public $backoff = [60, 120, 300];
//     public function handle(): void
//     {
//         Log::info('Welcome email started', [
//             'email' => $this->email,
//             'userType' => $this->userType
//         ]);

//         Mail::to($this->email)
//             ->send(
//                 new WelcomeEmail(
//                     $this->name,
//                     $this->couponCode,
//                     $this->userType
//                 )
//             );

//         Log::info('Welcome email completed');
//     }
// }

class SendWelcomeEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $backoff = [60, 120, 300];

    public function __construct(
        public string $email,
        public string $name,
        public ?string $couponCode = null,
        public string $userType = 'student'
    ) {}

    public function handle(): void
    {
        Log::info('Welcome email started', [
            'email' => $this->email,
            'userType' => $this->userType,
        ]);

        Mail::to($this->email)->send(
            new WelcomeEmail(
                $this->name,
                $this->couponCode,
                $this->userType
            )
        );

        Log::info('Welcome email completed', [
            'email' => $this->email,
        ]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Welcome email job failed', [
            'email' => $this->email,
            'userType' => $this->userType,
            'error' => $exception->getMessage(),
        ]);
    }
}
