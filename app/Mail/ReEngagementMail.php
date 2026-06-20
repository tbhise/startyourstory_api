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
     * @param string $name      Recipient display name.
     * @param string $userType  'student' | 'firm' | 'creator'.
     * @param bool   $verified  Whether the recipient's email is verified.
     * @param string $subjectLine Pre-built subject line.
     * @param array  $cta         Map of CTA URLs: ['login' => ..., 'profile' => ..., 'verify' => ...].
     */
    public function __construct(
        public string $name,
        public string $userType,
        public bool   $verified,
        public string $subjectLine,
        public array  $cta
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
                'name'     => $this->name,
                'userType' => $this->userType,
                'verified' => $this->verified,
                'cta'      => $this->cta,
            ]);
    }
}
