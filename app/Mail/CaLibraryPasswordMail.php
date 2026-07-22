<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

// CA Library password set/reset link. Serves both "forgot my password" and
// accounts created before a password was required (password IS NULL), which is
// why the wording is neutral ("set a password") rather than "reset".
class CaLibraryPasswordMail extends Mailable implements ShouldQueue, HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $passwordUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::VERIFICATION;
    }

    public function build()
    {
        return $this
            ->subject('Set your CA Library password')
            ->view('emails.ca-library-password');
    }
}
