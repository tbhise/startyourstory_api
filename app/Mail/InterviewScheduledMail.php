<?php

namespace App\Mail;

use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InterviewScheduledMail extends Mailable implements HasEmailPurpose
{
    use Queueable, SerializesModels;

    /**
     * Serves BOTH interview flows:
     *  - applications flow: $jobTitle set, default My Jobs respond CTA/copy.
     *  - invite flow (candidate profile): $jobTitle null (no job attached);
     *    pass $respondUrl/$respondLabel/$respondPage/$respondHint so the
     *    student is pointed at Recruiter Actions instead of My Jobs.
     * The trailing params are optional so existing callers are untouched.
     */
    public function __construct(
        public readonly string  $candidateName,
        public readonly string  $firmName,
        public readonly ?string $jobTitle,
        public readonly string  $interviewDate,
        public readonly string  $interviewMode,
        public readonly ?string $interviewNote,
        public readonly string  $acceptUrl,
        public readonly string  $rejectUrl,
        public readonly ?string $interviewLocation = null,
        public readonly ?string $respondUrl = null,
        public readonly ?string $respondLabel = null,
        public readonly ?string $respondPage = null,
        public readonly ?string $respondHint = null
    ) {}

    public function emailPurpose(): EmailPurpose
    {
        return EmailPurpose::INTERVIEW_SCHEDULED;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Interview Request from {$this->firmName} — Start Your Story",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.interview.scheduled',
            with: [
                'candidateName'     => $this->candidateName,
                'firmName'          => $this->firmName,
                'jobTitle'          => $this->jobTitle,
                'interviewDate'     => $this->interviewDate,
                'interviewMode'     => $this->interviewMode,
                'interviewNote'     => $this->interviewNote,
                'acceptUrl'         => $this->acceptUrl,
                'rejectUrl'         => $this->rejectUrl,
                'interviewLocation' => $this->interviewLocation,
                'respondUrl'        => $this->respondUrl,
                'respondLabel'      => $this->respondLabel,
                'respondPage'       => $this->respondPage,
                'respondHint'       => $this->respondHint,
            ],
        );
    }
}
