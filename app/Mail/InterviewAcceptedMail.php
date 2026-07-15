<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewAcceptedMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    /**
     * Serves BOTH interview flows: applications flow ($jobTitle set, default
     * "View Applications" CTA) and invite flow ($jobTitle null, CTA relabelled
     * via $ctaLabel and pointed at the candidate profile). Trailing param is
     * optional so existing callers are untouched.
     */
    public function __construct(
        public readonly string  $candidateName,
        public readonly ?string $jobTitle,
        public readonly string  $interviewDate,
        public readonly string  $interviewMode,
        public readonly string  $viewApplicationsUrl,
        public readonly ?string $ctaLabel = null
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::INTERVIEW_ACCEPTED;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->candidateName} Accepted Your Interview Request — Start Your Story",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview.accepted',
            with: [
                'candidateName'       => $this->candidateName,
                'jobTitle'            => $this->jobTitle,
                'interviewDate'       => $this->interviewDate,
                'interviewMode'       => $this->interviewMode,
                'viewApplicationsUrl' => $this->viewApplicationsUrl,
                'ctaLabel'            => $this->ctaLabel,
            ],
        );
    }
}
