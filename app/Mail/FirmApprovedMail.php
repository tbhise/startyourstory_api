<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class FirmApprovedMail extends Mailable implements HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $firmName
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::FIRM_APPROVED;
    }

    public function build()
    {
        return $this->subject('Your Firm Account Has Been Approved — Start Your Story')
            ->view('emails.firm-approved');
    }
}
