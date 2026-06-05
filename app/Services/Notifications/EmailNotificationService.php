<?php

namespace App\Services\Notifications;

use App\Enums\EmailPurpose;
use App\Jobs\DispatchMailJob;
use App\Jobs\SendVerificationEmailJob;
use App\Jobs\SendWelcomeEmailJob;
use App\Mail\ApplicationDigestMail;
use App\Mail\FirmApprovedMail;
use App\Mail\FirmRejectedMail;
use App\Mail\InterviewAcceptedMail;
use App\Mail\InterviewRejectedMail;
use App\Mail\InterviewScheduledMail;
use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Mail\Mailable;

class EmailNotificationService
{
    // -------------------------------------------------------------------------
    // Primitive: log + dispatch
    // -------------------------------------------------------------------------

    /**
     * Create a pending email_log entry, dispatch DispatchMailJob, return log ID.
     */
    private function queue(
        string   $recipientEmail,
        string   $recipientType,
        Mailable $mailable,
        string   $subject,
        EmailPurpose $purpose
    ): int {
        $log = EmailLog::create([
            'recipient_email' => $recipientEmail,
            'recipient_type'  => $recipientType,
            'email_purpose'   => $purpose->value,
            'template_name'   => class_basename($mailable),
            'sender_identity' => $purpose->senderKey(),
            'subject'         => $subject,
            'status'          => 'pending',
        ]);

        DispatchMailJob::dispatch($recipientEmail, $mailable, $log->id);

        return $log->id;
    }

    // -------------------------------------------------------------------------
    // Auth / Onboarding
    // -------------------------------------------------------------------------

    /**
     * Send email verification link.
     * Verification emails use a dedicated job because the signed URL must be
     * generated at execution time, not at dispatch time.
     */
    public function sendVerificationEmail(User $user): void
    {
        $log = EmailLog::create([
            'recipient_email' => $user->email,
            'recipient_type'  => $user->role ?? 'student',
            'email_purpose'   => EmailPurpose::VERIFICATION->value,
            'template_name'   => 'VerifyEmailMail',
            'sender_identity' => EmailPurpose::VERIFICATION->senderKey(),
            'subject'         => 'Verify Your Email Address',
            'status'          => 'pending',
        ]);

        SendVerificationEmailJob::dispatch($user, $log->id);
    }

    /**
     * Send welcome email after successful verification.
     */
    public function sendWelcomeEmail(
        string  $email,
        string  $name,
        ?string $couponCode = null,
        string  $userType   = 'student',
        int     $delaySeconds = 120
    ): void {
        $log = EmailLog::create([
            'recipient_email' => $email,
            'recipient_type'  => $userType,
            'email_purpose'   => EmailPurpose::WELCOME->value,
            'template_name'   => 'WelcomeEmail',
            'sender_identity' => EmailPurpose::WELCOME->senderKey(),
            'subject'         => 'Welcome to Start Your Story',
            'status'          => 'pending',
        ]);

        SendWelcomeEmailJob::dispatch($email, $name, $couponCode, $userType, $log->id)
            ->delay(now()->addSeconds($delaySeconds));
    }

    // -------------------------------------------------------------------------
    // Firm Verification
    // -------------------------------------------------------------------------

    public function sendFirmApproved(string $firmEmail, string $firmName): void
    {
        $mailable = new FirmApprovedMail($firmName);

        $this->queue(
            $firmEmail,
            'firm',
            $mailable,
            'Your Firm Account Has Been Approved — Start Your Story',
            EmailPurpose::FIRM_APPROVED
        );
    }

    public function sendFirmRejected(string $firmEmail, string $firmName, string $reason): void
    {
        $mailable = new FirmRejectedMail($firmName, $reason);

        $this->queue(
            $firmEmail,
            'firm',
            $mailable,
            'Update on Your Firm Account — Start Your Story',
            EmailPurpose::FIRM_REJECTED
        );
    }

    // -------------------------------------------------------------------------
    // Interview — Candidate emails
    // -------------------------------------------------------------------------

    public function sendInterviewScheduled(
        string  $studentEmail,
        string  $studentName,
        string  $firmName,
        string  $jobTitle,
        string  $interviewDate,
        string  $interviewMode,
        ?string $interviewNote,
        int     $applicationId
    ): void {
        $base      = config('app.frontend_url', 'https://startyourstory.in');
        $acceptUrl = "{$base}/my-applications/{$applicationId}?action=accept";
        $rejectUrl = "{$base}/my-applications/{$applicationId}?action=reject";

        $mailable = new InterviewScheduledMail(
            $studentName,
            $firmName,
            $jobTitle,
            $interviewDate,
            $interviewMode,
            $interviewNote,
            $acceptUrl,
            $rejectUrl
        );

        $this->queue(
            $studentEmail,
            'student',
            $mailable,
            "Interview Request from {$firmName} — Start Your Story",
            EmailPurpose::INTERVIEW_SCHEDULED
        );
    }

    // -------------------------------------------------------------------------
    // Interview — Firm emails (sent when candidate responds)
    // -------------------------------------------------------------------------

    public function sendInterviewAccepted(
        string $firmEmail,
        string $candidateName,
        string $jobTitle,
        string $interviewDate,
        string $interviewMode,
        string $viewApplicationsUrl
    ): void {
        $mailable = new InterviewAcceptedMail(
            $candidateName,
            $jobTitle,
            $interviewDate,
            $interviewMode,
            $viewApplicationsUrl
        );

        $this->queue(
            $firmEmail,
            'firm',
            $mailable,
            "{$candidateName} Accepted Your Interview Request — Start Your Story",
            EmailPurpose::INTERVIEW_ACCEPTED
        );
    }

    public function sendInterviewRejected(
        string $firmEmail,
        string $candidateName,
        string $jobTitle,
        string $viewApplicationsUrl
    ): void {
        $mailable = new InterviewRejectedMail(
            $candidateName,
            $jobTitle,
            $viewApplicationsUrl
        );

        $this->queue(
            $firmEmail,
            'firm',
            $mailable,
            "{$candidateName} Declined Your Interview Request — Start Your Story",
            EmailPurpose::INTERVIEW_REJECTED
        );
    }

    // -------------------------------------------------------------------------
    // Application Digest — called by SendApplicationDigestJob
    // -------------------------------------------------------------------------

    public function sendApplicationDigest(
        string $firmEmail,
        string $firmName,
        array  $applications,
        string $viewApplicationsUrl
    ): int {
        $count    = count($applications);
        $noun     = $count === 1 ? 'New Application' : 'New Applications';
        $mailable = new ApplicationDigestMail($firmName, $applications, $viewApplicationsUrl);

        return $this->queue(
            $firmEmail,
            'firm',
            $mailable,
            "{$count} {$noun} Received — Start Your Story",
            EmailPurpose::APPLICATION_DIGEST
        );
    }
}
