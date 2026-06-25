<?php

namespace App\Services\Notifications;

use App\Enums\EmailPurpose;
use App\Jobs\DispatchMailJob;
use App\Jobs\SendVerificationEmailJob;
use App\Jobs\SendWelcomeEmailJob;
use App\Mail\ApplicationDigestMail;
use App\Mail\CreatorAcceptedMail;
use App\Mail\CreatorSelectedMail;
use App\Mail\PasswordResetMail;
use App\Mail\FirmApprovedMail;
use App\Mail\FirmRejectedMail;
use App\Mail\InterviewAcceptedMail;
use App\Mail\InterviewInviteMail;
use App\Mail\InterviewInviteResponseMail;
use App\Mail\InterviewRejectedMail;
use App\Mail\InterviewRescheduleAcceptedMail;
use App\Mail\InterviewScheduledMail;
use App\Mail\ReferralPayoutRequestMail;
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
     * Send password reset email containing the signed reset URL.
     */
    public function sendPasswordResetEmail(string $email, string $resetUrl): void
    {
        $mailable = new PasswordResetMail($resetUrl);

        $this->queue(
            $email,
            'user',
            $mailable,
            'Reset Your Password — Start Your Story',
            EmailPurpose::PASSWORD_RESET
        );
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

    public function sendInterviewInvite(
        string  $studentEmail,
        string  $studentName,
        string  $firmName,
        ?string $inviteMessage
    ): void {
        $base       = config('app.frontend_url', config('app.url', 'https://startyourstory.in'));
        $respondUrl = "{$base}/recruiter-actions";

        $mailable = new InterviewInviteMail(
            $studentName,
            $firmName,
            $inviteMessage,
            $respondUrl
        );

        $this->queue(
            $studentEmail,
            'student',
            $mailable,
            "{$firmName} invited you for an interview — Start Your Story",
            EmailPurpose::INTERVIEW_INVITE
        );
    }

    public function sendInterviewInviteResponse(
        string $firmEmail,
        string $firmName,
        string $candidateName,
        bool   $accepted
    ): void {
        $base    = config('app.frontend_url', config('app.url', 'https://startyourstory.in'));
        $viewUrl = "{$base}/firm-students";

        $mailable = new InterviewInviteResponseMail(
            $firmName,
            $candidateName,
            $accepted,
            $viewUrl
        );

        $verb = $accepted ? 'accepted' : 'declined';
        $this->queue(
            $firmEmail,
            'firm',
            $mailable,
            "{$candidateName} {$verb} your interview invitation — Start Your Story",
            EmailPurpose::INTERVIEW_INVITE_RESPONSE
        );
    }

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
    // Interview — sent to student when firm accepts reschedule
    // -------------------------------------------------------------------------

    public function sendInterviewRescheduleAccepted(
        string  $studentEmail,
        string  $studentName,
        string  $firmName,
        string  $jobTitle,
        string  $interviewDate,
        ?string $interviewNote
    ): void {
        $mailable = new InterviewRescheduleAcceptedMail(
            $studentName,
            $firmName,
            $jobTitle,
            $interviewDate,
            $interviewNote
        );

        $this->queue(
            $studentEmail,
            'student',
            $mailable,
            "Interview Reschedule Accepted — {$firmName}",
            EmailPurpose::INTERVIEW_RESCHEDULE_ACCEPTED
        );
    }

    // -------------------------------------------------------------------------
    // Creator Marketplace
    // -------------------------------------------------------------------------

    /** Sent to creator when a firm selects their bid. */
    public function sendCreatorSelected(
        string $creatorEmail,
        string $creatorName,
        string $projectTitle,
        int    $bidId
    ): void {
        $base       = config('app.frontend_url', 'https://startyourstory.in');
        $respondUrl = "{$base}/creator-marketplace/my-bids/{$bidId}";

        $mailable = new CreatorSelectedMail($creatorName, $projectTitle, $respondUrl);

        $this->queue(
            $creatorEmail,
            'student',
            $mailable,
            "You've been selected for \"{$projectTitle}\" — Start Your Story",
            EmailPurpose::CREATOR_SELECTED
        );
    }

    /** Sent to firm when creator accepts the contract. */
    public function sendCreatorAccepted(
        string $firmEmail,
        string $firmName,
        string $creatorName,
        string $projectTitle,
        int    $engagementId
    ): void {
        $base        = config('app.frontend_url', 'https://startyourstory.in');
        $contractUrl = "{$base}/creator-marketplace/engagement/{$engagementId}";

        $mailable = new CreatorAcceptedMail($firmName, $creatorName, $projectTitle, $contractUrl);

        $this->queue(
            $firmEmail,
            'firm',
            $mailable,
            "{$creatorName} accepted \"{$projectTitle}\" — Start Your Story",
            EmailPurpose::CREATOR_ACCEPTED
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

    // -------------------------------------------------------------------------
    // Referral payouts — request payout details from the referrer (admin-triggered)
    // -------------------------------------------------------------------------

    /**
     * Ask a referrer to add their payout details so a pending reward can be paid.
     * Logged in email_logs via the shared queue() primitive.
     */
    public function sendReferralPayoutRequest(
        string $email,
        string $name,
        float  $amount,
        string $payoutUrl
    ): int {
        $mailable = new ReferralPayoutRequestMail($name, $amount, $payoutUrl);

        return $this->queue(
            $email,
            'user',
            $mailable,
            'Action needed: add your payout details — Start Your Story',
            EmailPurpose::REFERRAL_PAYOUT_REQUEST
        );
    }
}
