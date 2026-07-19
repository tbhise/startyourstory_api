<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

// CA Library evaluation complete. Link to My Library only — never attach the PDF.
class CaLibraryEvaluatedMail extends Mailable implements ShouldQueue, HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $materialTitle,
        public string $myLibraryUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::CA_LIBRARY_EVALUATED;
    }

    public function build()
    {
        return $this
            ->subject('Your evaluated answer sheet is ready — CA Library')
            ->view('emails.ca-library-evaluated');
    }
}
