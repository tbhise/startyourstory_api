<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/**
 * Premium subscription activation confirmation (firm).
 *
 * ONE template serves all three activation flows — $activationType picks the
 * confirmation line:
 *   'phonepe'          → PhonePe payment verified (verify() or webhook())
 *   'admin_assigned'   → admin manually assigned a subscription
 *   'request_approved' → admin approved a Premium Request
 */
class PremiumSubscriptionActivatedMail extends Mailable implements HasEmailPurpose
{
    use Queueable;

    public function __construct(
        public string  $firmName,
        public string  $activationType,
        public string  $planName,
        public string  $subscriptionPeriod,
        public string  $activationDate,
        public ?string $expiryDate,
        public string  $amount,
        public string  $paymentMethod,
        public ?string $invoiceNumber,
        public string  $billingUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::PREMIUM_ACTIVATED;
    }

    public function build()
    {
        return $this->subject('Premium Subscription Activated')
            ->view('emails.firm.premium-activated');
    }
}
