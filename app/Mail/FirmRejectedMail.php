<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class FirmRejectedMail extends Mailable implements HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $firmName,
        public string $reason
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::FIRM_REJECTED;
    }

    public function build()
    {
        return $this->subject('Update on Your Firm Account — Start Your Story')
            ->view('emails.firm-rejected');
    }
}
