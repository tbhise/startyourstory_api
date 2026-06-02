<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class FirmRejectedMail extends Mailable
{
    use Queueable;

    public function __construct(
        public string $firmName,
        public string $reason
    ) {}

    public function build()
    {
        return $this->subject('Update on Your Firm Account — Start Your Story')
            ->view('emails.firm-rejected');
    }
}
