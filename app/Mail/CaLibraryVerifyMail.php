<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

// CA Library download verification. Link only — never attach the PDF.
class CaLibraryVerifyMail extends Mailable implements ShouldQueue, HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $verificationUrl,
        public string $materialTitle
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::VERIFICATION;
    }

    public function build()
    {
        return $this
            ->subject('Verify your email to download from CA Library')
            ->view('emails.ca-library-verify');
    }
}
