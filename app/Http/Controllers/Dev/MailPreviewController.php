<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationDigestMail;
use App\Mail\CreatorAcceptedMail;
use App\Mail\CreatorSelectedMail;
use App\Mail\FirmApplicantReminderMail;
use App\Mail\FirmApprovedMail;
use App\Mail\FirmFeatureReleaseMail;
use App\Mail\FirmRejectedMail;
use App\Mail\InterviewAcceptedMail;
use App\Mail\InterviewInviteMail;
use App\Mail\InterviewInviteResponseMail;
use App\Mail\InterviewRejectedMail;
use App\Mail\InterviewReminder1HourMail;
use App\Mail\InterviewReminder24HourMail;
use App\Mail\InterviewRescheduleAcceptedMail;
use App\Mail\InterviewResponseReminderMail;
use App\Mail\InterviewScheduledMail;
use App\Mail\NewMessageReplyMail;
use App\Mail\NewMessageRequestMail;
use App\Mail\PasswordResetMail;
use App\Mail\ReEngagementMail;
use App\Mail\ReferralPayoutRequestMail;
use App\Mail\StudentFeatureReleaseMail;
use App\Mail\SupportTicketClosedMail;
use App\Contracts\Mail\HasEmailPurpose;
use App\Enums\EmailPurpose;
use App\Jobs\DispatchMailJob;
use App\Mail\VerifyEmailMail;
use App\Mail\WelcomeEmail;
use App\Models\EmailLog;
use Illuminate\Http\Request;
use Illuminate\Mail\Mailable;

/**
 * LOCAL-ONLY browser preview of every email template, for designing/tweaking
 * the Blade views without sending real mail.
 *
 *   GET /dev/emails          → index of all templates
 *   GET /dev/emails/{key}    → the rendered email HTML (edit blade, refresh)
 *
 * Routes are registered only when APP_ENV=local (see routes/web.php) and the
 * controller re-checks the environment as a second gate, so this can never be
 * reached on production even if the route file is edited carelessly.
 *
 * To add a template: add one registry() entry — key => [group, description,
 * factory closure returning the Mailable with realistic sample data].
 */
class MailPreviewController extends Controller
{
    /** Default recipient for /dev/emails/{key}/send (override with ?to=). */
    private const TEST_RECIPIENT = 'tusharbhise908@gmail.com';

    /**
     * @param  string|null $candidate  overrides the sample candidate name (?name=)
     * @return array<string, array{group: string, desc: string, make: \Closure(): Mailable}>
     */
    private function registry(?string $candidate = null): array
    {
        $front     = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');
        $candidate = $candidate ?: 'Student A';
        $firm      = 'Firm X LLP';
        $jobTitle  = 'Articleship Trainee - Indirect Tax (GST & Customs)';
        $date      = 'Mon, 13 Jul 2026 at 11:00 AM';

        return [
            // ── Campaigns (premium layout) ───────────────────────────────────
            'student-feature-release' => [
                'group' => 'Campaigns',
                'desc'  => 'Re-engagement + feature release (premium header/footer layout)',
                'make'  => fn () => new StudentFeatureReleaseMail($candidate),
            ],
            'firm-reengagement-feature' => [
                'group' => 'Campaigns',
                'desc'  => 'Firm re-engagement + feature release (firm counterpart)',
                'make'  => fn () => new FirmFeatureReleaseMail(),
            ],

            // ── Account ──────────────────────────────────────────────────────
            'welcome-student' => [
                'group' => 'Account',
                'desc'  => 'Welcome mail (student, with coupon)',
                'make'  => fn () => new WelcomeEmail($candidate, 'SYS500', 'student'),
            ],
            'welcome-firm' => [
                'group' => 'Account',
                'desc'  => 'Welcome mail (firm, with coupon — production passes the firm\'s referral_code)',
                'make'  => fn () => new WelcomeEmail($firm, 'WELCOME100', 'firm'),
            ],
            'verify-email' => [
                'group' => 'Account',
                'desc'  => 'Email verification link',
                'make'  => fn () => new VerifyEmailMail($candidate, $front . '/verify-email?token=SAMPLE'),
            ],
            'password-reset' => [
                'group' => 'Account',
                'desc'  => 'Password reset link',
                'make'  => fn () => new PasswordResetMail($front . '/reset-password?token=SAMPLE'),
            ],
            're-engagement' => [
                'group' => 'Account',
                'desc'  => 'Re-engagement nudge (inactive user)',
                'make'  => fn () => new ReEngagementMail($candidate, 'student', true, false, 'Your dream articleship is waiting', $front . '/?utm=re-engagement'),
            ],

            // ── Firm verification ────────────────────────────────────────────
            'firm-approved' => [
                'group' => 'Firm verification',
                'desc'  => 'Firm account approved',
                'make'  => fn () => new FirmApprovedMail($firm),
            ],
            'firm-rejected' => [
                'group' => 'Firm verification',
                'desc'  => 'Firm account rejected (with reason)',
                'make'  => fn () => new FirmRejectedMail($firm, 'The uploaded registration certificate is not legible. Please re-upload a clear copy.'),
            ],

            // ── Applications ─────────────────────────────────────────────────
            'application-digest' => [
                'group' => 'Applications',
                'desc'  => 'Daily digest of new applications (firm)',
                'make'  => fn () => new ApplicationDigestMail($firm, [
                    ['candidate_name' => $candidate, 'job_title' => $jobTitle, 'applied_at' => '05 Jul 2026, 03:56 AM'],
                    ['candidate_name' => 'Prachi Mishra', 'job_title' => 'Articleship Trainee - Direct Tax', 'applied_at' => '05 Jul 2026, 09:12 AM'],
                ], $front . '/firm-applications'),
            ],
            'firm-applicant-reminder' => [
                'group' => 'Applications',
                'desc'  => 'Applicants awaiting review reminder (firm)',
                'make'  => fn () => new FirmApplicantReminderMail($firm, [
                    ['title' => $jobTitle, 'count' => 4],
                    ['title' => 'Articleship Trainee - Direct Tax', 'count' => 2],
                ], 6, $front . '/firm-applications'),
            ],

            // ── Interviews ───────────────────────────────────────────────────
            'interview-invite' => [
                'group' => 'Interviews',
                'desc'  => 'Firm invites candidate to interview',
                'make'  => fn () => new InterviewInviteMail($candidate, $firm, 'We liked your profile and would love to talk about the GST articleship role.', $front . '/recruiter-actions'),
            ],
            'interview-invite-accepted' => [
                'group' => 'Interviews',
                'desc'  => 'Candidate accepted the invite (to firm)',
                'make'  => fn () => new InterviewInviteResponseMail($firm, $candidate, true, $front . '/firm-students'),
            ],
            'interview-invite-declined' => [
                'group' => 'Interviews',
                'desc'  => 'Candidate declined the invite (to firm)',
                'make'  => fn () => new InterviewInviteResponseMail($firm, $candidate, false, $front . '/firm-students'),
            ],
            'interview-scheduled' => [
                'group' => 'Interviews',
                'desc'  => 'Interview scheduled (to candidate, accept/reject CTAs)',
                'make'  => fn () => new InterviewScheduledMail($candidate, $firm, $jobTitle, $date, 'Online (Google Meet)', 'Please keep your Form 102 handy.', $front . '/applications?respond=accept', $front . '/applications?respond=reject'),
            ],
            'interview-accepted' => [
                'group' => 'Interviews',
                'desc'  => 'Candidate accepted the interview (to firm)',
                'make'  => fn () => new InterviewAcceptedMail($candidate, $jobTitle, $date, 'Online (Google Meet)', $front . '/firm-jobs'),
            ],
            'interview-rejected' => [
                'group' => 'Interviews',
                'desc'  => 'Candidate rejected the interview (to firm)',
                'make'  => fn () => new InterviewRejectedMail($candidate, $jobTitle, $front . '/firm-jobs'),
            ],
            'interview-reminder-24h' => [
                'group' => 'Interviews',
                'desc'  => 'Interview reminder, 24 hours before',
                'make'  => fn () => new InterviewReminder24HourMail($candidate, $firm, $jobTitle, $date, 'Online (Google Meet)', 'Please keep your Form 102 handy.', $front . '/applications'),
            ],
            'interview-reminder-1h' => [
                'group' => 'Interviews',
                'desc'  => 'Interview reminder, 1 hour before',
                'make'  => fn () => new InterviewReminder1HourMail($candidate, $firm, $jobTitle, $date, 'Online (Google Meet)', null, $front . '/applications'),
            ],
            'interview-reschedule-accepted' => [
                'group' => 'Interviews',
                'desc'  => 'Reschedule request accepted (to candidate)',
                'make'  => fn () => new InterviewRescheduleAcceptedMail($candidate, $firm, $jobTitle, $date, 'New slot confirmed by the firm.'),
            ],
            'interview-response-reminder' => [
                'group' => 'Interviews',
                'desc'  => 'Pending response nudge (to candidate)',
                'make'  => fn () => new InterviewResponseReminderMail($candidate, $firm, $front . '/applications', false),
            ],
            'interview-response-reminder-final' => [
                'group' => 'Interviews',
                'desc'  => 'Pending response FINAL nudge (to candidate)',
                'make'  => fn () => new InterviewResponseReminderMail($candidate, $firm, $front . '/applications', true),
            ],

            // ── Messaging ────────────────────────────────────────────────────
            'message-request-from-firm' => [
                'group' => 'Messaging',
                'desc'  => 'New message request (firm → candidate)',
                'make'  => fn () => new NewMessageRequestMail(
                    (object) ['name' => $candidate],
                    (object) ['firm_name' => $firm],
                    'Hi Devesh, we reviewed your application and would like to discuss the role. Are you available this week?'
                ),
            ],
            'message-request-from-candidate' => [
                'group' => 'Messaging',
                'desc'  => 'New message request (candidate → firm)',
                'make'  => fn () => new NewMessageRequestMail(
                    (object) ['name' => $firm],
                    null,
                    'Hello, I wanted to follow up on my application for the GST articleship.',
                    (object) ['name' => $candidate]
                ),
            ],
            'message-reply' => [
                'group' => 'Messaging',
                'desc'  => 'New reply in an existing conversation',
                'make'  => fn () => new NewMessageReplyMail(
                    (object) ['name' => $candidate],
                    $firm,
                    'Great — let\'s schedule a quick call tomorrow at 11 AM.'
                ),
            ],

            // ── Creator marketplace ──────────────────────────────────────────
            'creator-selected' => [
                'group' => 'Creator marketplace',
                'desc'  => 'Creator selected for a project',
                'make'  => fn () => new CreatorSelectedMail($candidate, 'Instagram reel series — Tax tips for freshers', $front . '/creator-marketplace'),
            ],
            'creator-accepted' => [
                'group' => 'Creator marketplace',
                'desc'  => 'Creator accepted the engagement (to firm)',
                'make'  => fn () => new CreatorAcceptedMail($firm, $candidate, 'Instagram reel series — Tax tips for freshers', $front . '/creator-marketplace'),
            ],

            // ── Misc ─────────────────────────────────────────────────────────
            'referral-payout-request' => [
                'group' => 'Misc',
                'desc'  => 'Referral payout requested (to admin)',
                'make'  => fn () => new ReferralPayoutRequestMail($candidate, 2000.00, $front . '/admin/referral-payouts'),
            ],
            'support-ticket-closed' => [
                'group' => 'Misc',
                'desc'  => 'Support ticket resolved',
                'make'  => fn () => new SupportTicketClosedMail($candidate, 'SYS-2026-00042', 'Payments & Wallet', 'The failed recharge of ₹500 has been refunded to your original payment method.'),
            ],
        ];
    }

    public function index()
    {
        abort_unless(app()->environment(['local', 'development']), 404);

        $grouped = [];
        foreach ($this->registry() as $key => $def) {
            $grouped[$def['group']][$key] = $def['desc'];
        }

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Email previews</title>'
            . '<style>body{font:15px/1.6 system-ui,sans-serif;max-width:720px;margin:40px auto;padding:0 16px;color:#1a1a2e}'
            . 'h1{font-size:22px}h2{font-size:15px;margin:28px 0 8px;color:#666;text-transform:uppercase;letter-spacing:.05em}'
            . 'a{color:#0f766e;text-decoration:none;font-weight:600}a:hover{text-decoration:underline}'
            . 'li{margin:6px 0}span{color:#888;font-weight:400}'
            . 'a.mode{font-weight:400;font-size:11.5px;color:#888;border:1px solid #ddd;border-radius:10px;padding:1px 7px;margin-left:2px}</style></head><body>'
            . '<h1>📧 Email template previews</h1>'
            . '<p>Click a template, edit its blade file under <code>resources/views/emails/</code>, refresh the tab. Local environment only.<br>'
            . '<small>Params: <code>?name=Tushar</code> (sample candidate name, works on previews and sends) · send: <code>?to=you@x.com</code> · '
            . '<code>?via=smtp</code> for real delivery when the default mailer is <code>log</code>.</small></p>';

        foreach ($grouped as $group => $items) {
            $html .= '<h2>' . e($group) . '</h2><ul>';
            foreach ($items as $key => $desc) {
                $html .= '<li><a href="/dev/emails/' . e($key) . '">' . e($key) . '</a>'
                    . ' <a href="/dev/emails/' . e($key) . '?mode=light" class="mode">☀ light</a>'
                    . ' <a href="/dev/emails/' . e($key) . '?mode=dark" class="mode">🌙 dark</a>'
                    . ' <a href="/dev/emails/' . e($key) . '/send" class="mode">✉ send test</a>'
                    . ' <span>— ' . e($desc) . '</span></li>';
            }
            $html .= '</ul>';
        }

        return response($html . '</body></html>');
    }

    public function show(Request $request, string $key)
    {
        abort_unless(app()->environment(['local', 'development']), 404);

        $registry = $this->registry($request->query('name'));
        abort_unless(isset($registry[$key]), 404, 'Unknown email key');

        $html = $registry[$key]['make']()->render();

        // ?mode=light|dark pins the colour scheme for design work regardless of
        // the OS/browser theme. Default (no param) follows prefers-color-scheme,
        // exactly like a real mail client. Preview-only trick: rewrite the dark
        // media query condition so it never / always matches.
        $mode = $request->query('mode');
        if ($mode === 'light') {
            $html = str_ireplace('(prefers-color-scheme: dark)', 'not all', $html);
        } elseif ($mode === 'dark') {
            $html = str_ireplace('(prefers-color-scheme: dark)', 'all', $html);
        }

        return response($html);
    }

    /*
    |--------------------------------------------------------------------------
    | GET /dev/emails/{key}/send — send a REAL test email for any template
    |--------------------------------------------------------------------------
    | Params: ?to=addr   recipient (default self::TEST_RECIPIENT)
    |         ?name=X    overrides the sample candidate name in the template
    |         ?via=smtp  force a configured mailer (local default is 'log',
    |                    which only writes to storage/logs/laravel.log)
    |
    | Deliberately reuses the EXACT production pipeline — an email_logs row plus
    | DispatchMailJob (sender-identity resolution by purpose, send, markSent/
    | markFailed) — run synchronously via dispatchSync so the outcome is known
    | in the response. No duplicated send logic.
    */
    public function send(Request $request, string $key)
    {
        abort_unless(app()->environment(['local', 'development']), 404);

        $registry = $this->registry($request->query('name'));
        abort_unless(isset($registry[$key]), 404, 'Unknown email key');

        $to = $request->query('to', self::TEST_RECIPIENT);
        abort_unless((bool) filter_var($to, FILTER_VALIDATE_EMAIL), 422, 'Invalid ?to address');

        $mailable = $registry[$key]['make']();

        if ($via = $request->query('via')) {
            abort_unless(config("mail.mailers.{$via}") !== null, 422, "Mailer '{$via}' is not configured");
            $mailable->mailer($via);
        }

        $purpose = $mailable instanceof HasEmailPurpose
            ? $mailable->emailPurpose()
            : EmailPurpose::MARKETING;

        $subject = method_exists($mailable, 'envelope')
            ? ($mailable->envelope()->subject ?? class_basename($mailable))
            : class_basename($mailable);

        // Same row shape EmailNotificationService::queue() writes, flagged [TEST].
        $log = EmailLog::create([
            'recipient_email' => $to,
            'recipient_type'  => $purpose->recipientType(),
            'email_purpose'   => $purpose->value,
            'template_name'   => class_basename($mailable),
            'sender_identity' => $purpose->senderKey(),
            'subject'         => '[TEST] ' . $subject,
            'status'          => 'pending',
        ]);

        try {
            DispatchMailJob::dispatchSync($to, $mailable, $log->id);
        } catch (\Throwable $e) {
            $log->refresh();
            if ($log->status === 'pending') {
                $log->markFailed(mb_substr($e->getMessage(), 0, 500));
            }
            return response()->json([
                'status'   => false,
                'template' => $key,
                'to'       => $to,
                'error'    => $e->getMessage(),
                'email_log_id' => $log->id,
            ], 500);
        }

        $mailerUsed = $via ?: config('mail.default');

        return response()->json([
            'status'       => true,
            'template'     => $key,
            'sent_to'      => $to,
            'subject'      => '[TEST] ' . $subject,
            'mailer'       => $mailerUsed,
            'email_log_id' => $log->id,
            'note'         => $mailerUsed === 'log'
                ? "Default mailer is 'log' — the email was written to storage/logs/laravel.log, NOT delivered. Add ?via=smtp for real delivery."
                : null,
        ]);
    }
}
