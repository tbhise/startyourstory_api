<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/**
 * Admin-triggered request asking a referrer to submit their payout details so a
 * pending referral reward can be settled. Reuses the standard mail stack
 * (DispatchMailJob + email_logs via EmailNotificationService).
 */
class ReferralPayoutRequestMail extends Mailable implements HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string $name,
        public float  $amount,
        public string $payoutUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::REFERRAL_PAYOUT_REQUEST;
    }

    public function build()
    {
        return $this->subject('Action needed: add your payout details — Start Your Story')
            ->view('emails.referral.payout-request')
            ->with([
                'name'      => $this->name,
                'amount'    => $this->amount,
                'payoutUrl' => $this->payoutUrl,
            ]);
    }
}
