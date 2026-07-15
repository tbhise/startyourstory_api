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
use App\Mail\InterviewResponseReminderMail;
use App\Mail\FirmApplicantReminderMail;
use App\Mail\ReferralPayoutRequestMail;
use App\Mail\StudentFeatureReleaseMail;
use App\Mail\FirmFeatureReleaseMail;
use App\Mail\SupportTicketClosedMail;
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
    // Campaigns (bulk — queued by the mail:*-reengagement-feature commands via
    // CampaignEmailService; standard log + DispatchMailJob pipeline)
    // -------------------------------------------------------------------------

    /**
     * Student re-engagement + feature release campaign email.
     */
    public function sendStudentFeatureReleaseEmail(string $email, string $name = ''): int
    {
        $subject  = '🚀 Big Updates Now Live on StartYourStory';
        $mailable = (new StudentFeatureReleaseMail($name !== '' ? $name : 'Candidate'))
            ->subject($subject);

        return $this->queue($email, 'student', $mailable, $subject, EmailPurpose::REENGAGEMENT);
    }

    /**
     * Firm re-engagement + feature release campaign email.
     */
    public function sendFirmFeatureReleaseEmail(string $email, string $name = ''): int
    {
        $subject  = '🚀 New Hiring Features Now Live on StartYourStory';
        $mailable = (new FirmFeatureReleaseMail($name !== '' ? $name : 'Hiring Partner'))
            ->subject($subject);

        return $this->queue($email, 'firm', $mailable, $subject, EmailPurpose::REENGAGEMENT);
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

    /**
     * Interview scheduled — INVITE flow (candidate-profile invitations).
     * REUSES InterviewScheduledMail / emails.interview.scheduled: no job is
     * attached ($jobTitle null) and the student responds on the Recruiter
     * Actions page instead of My Jobs.
     */
    public function sendInterviewInviteScheduled(
        string  $studentEmail,
        string  $studentName,
        string  $firmName,
        string  $interviewDate,
        string  $interviewMode,
        ?string $interviewLocation,
        ?string $interviewNote
    ): void {
        $base       = config('app.frontend_url', config('app.url', 'https://startyourstory.in'));
        $respondUrl = "{$base}/recruiter-actions";

        $mailable = new InterviewScheduledMail(
            $studentName,
            $firmName,
            null,               // no job — invitation from the candidate profile
            $interviewDate,
            $interviewMode,
            $interviewNote,
            $respondUrl,        // accept/reject URLs unused by the template
            $respondUrl,
            $interviewLocation,
            $respondUrl,
            'View & Respond',
            'Recruiter Actions',
            'Open your Recruiter Actions page, find this interview, and use the Accept, Reject, or Request Reschedule buttons to respond.'
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

    /**
     * Interview confirmed — INVITE flow (student confirmed the scheduled
     * interview). REUSES InterviewAcceptedMail / emails.interview.accepted:
     * no job is attached and the CTA deep-links to the candidate profile.
     */
    public function sendInterviewInviteConfirmed(
        string $firmEmail,
        string $candidateName,
        string $interviewDate,
        string $interviewMode,
        int    $studentId
    ): void {
        $base    = config('app.frontend_url', config('app.url', 'https://startyourstory.in'));
        $viewUrl = "{$base}/firm-students/{$studentId}";

        $mailable = new InterviewAcceptedMail(
            $candidateName,
            null,               // no job — invitation from the candidate profile
            $interviewDate,
            $interviewMode,
            $viewUrl,
            'View Candidate'
        );

        $this->queue(
            $firmEmail,
            'firm',
            $mailable,
            "{$candidateName} Accepted Your Interview Request — Start Your Story",
            EmailPurpose::INTERVIEW_ACCEPTED
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
    // Reminders — pending interview-invite response (student) + applicants
    // awaiting review (firm). Called by the scheduled reminder jobs.
    // -------------------------------------------------------------------------

    /**
     * Remind a student that a firm is still waiting for their interview-invite
     * response. $isFinal marks the last (5-day) reminder.
     */
    public function sendInterviewResponseReminder(
        string $studentEmail,
        string $studentName,
        string $firmName,
        bool   $isFinal = false
    ): int {
        $base       = config('app.frontend_url', config('app.url', 'https://startyourstory.in'));
        $respondUrl = "{$base}/recruiter-actions";

        $mailable = new InterviewResponseReminderMail($studentName, $firmName, $respondUrl, $isFinal);

        return $this->queue(
            $studentEmail,
            'student',
            $mailable,
            'Interview Response Pending',
            EmailPurpose::INTERVIEW_RESPONSE_REMINDER
        );
    }

    /**
     * Remind a firm that one or more of its active jobs have applicants awaiting
     * review.
     *
     * @param array<int,array{title:string,count:int}> $jobs
     */
    public function sendFirmApplicantReminder(
        string $firmEmail,
        string $firmName,
        array  $jobs,
        int    $totalCount
    ): int {
        $base    = config('app.frontend_url', 'https://startyourstory.in');
        $viewUrl = "{$base}/firm-jobs";

        $mailable = new FirmApplicantReminderMail($firmName, $jobs, $totalCount, $viewUrl);

        $noun = $totalCount === 1 ? 'applicant is' : 'applicants are';

        return $this->queue(
            $firmEmail,
            'firm',
            $mailable,
            "{$totalCount} {$noun} waiting for your review — Start Your Story",
            EmailPurpose::FIRM_APPLICANT_REMINDER
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

    // -------------------------------------------------------------------------
    // Support tickets — sent ONLY when a ticket is closed (with resolution note)
    // -------------------------------------------------------------------------

    public function sendSupportTicketClosed(
        string $email,
        string $userName,
        string $ticketNo,
        string $ticketCategory,
        string $resolutionNote
    ): int {
        $mailable = new SupportTicketClosedMail($userName, $ticketNo, $ticketCategory, $resolutionNote);

        return $this->queue(
            $email,
            'user',
            $mailable,
            "Your Support Ticket {$ticketNo} Has Been Resolved — Start Your Story",
            EmailPurpose::SUPPORT_TICKET_CLOSED
        );
    }
}
