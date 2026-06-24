<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

/**
 * Re-engagement campaign email.
 *
 * Reusable for every segment (student / firm / creator × verified / unverified).
 * The console command `mail:reengagement` resolves the right copy and CTA set per
 * recipient and passes them in; the Blade view renders accordingly.
 */
class ReEngagementMail extends Mailable implements HasEmailPurpose
{
    use Queueable;

    /**
     * @param string $name             Recipient display name.
     * @param string $userType         'student' | 'firm' | 'creator'.
     * @param bool   $verified         Whether the recipient's email is verified.
     * @param bool   $profileCompleted Whether the recipient's profile is complete.
     * @param string $subjectLine      Pre-built subject line.
     * @param string $trackingUrl      Single CTA target (signed click-tracking route).
     */
    public function __construct(
        public string $name,
        public string $userType,
        public bool   $verified,
        public bool   $profileCompleted,
        public string $subjectLine,
        public string $trackingUrl
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::REENGAGEMENT;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
            ->view('emails.reengagement')
            ->with([
                'name'             => $this->name,
                'userType'         => $this->userType,
                'verified'         => $this->verified,
                'profileCompleted' => $this->profileCompleted,
                'trackingUrl'      => $this->trackingUrl,
            ]);
    }
}
