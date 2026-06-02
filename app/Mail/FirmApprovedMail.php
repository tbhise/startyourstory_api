<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class FirmApprovedMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $firmName
    ) {}

    public function build()
    {
        return $this->subject('Your Firm Account Has Been Approved — Start Your Story')
            ->view('emails.firm-approved');
    }
}
