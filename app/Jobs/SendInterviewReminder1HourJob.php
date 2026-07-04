<?php

namespace App\Jobs;

use App\Enums\EmailPurpose;
use App\Mail\InterviewReminder1HourMail;
use App\Models\EmailLog;
use App\Services\Email\EmailSenderResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Scheduled job — runs every 30 minutes.
 * Finds confirmed/pending interviews due in 45–90 minutes, sends one reminder
 * per application. Duplicate-safe: reminder_1h_sent_at is set only after a
 * successful send so a failed delivery can be retried on the next run.
 */
class SendInterviewReminder1HourJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $windowStart = now()->addMinutes(45);
        $windowEnd   = now()->addMinutes(90);

        $applications = DB::table('applications')
            ->join('jobs',          'applications.job_id',     '=', 'jobs.id')
            ->join('users',         'applications.student_id', '=', 'users.id')
            ->join('firm_profiles', 'jobs.firm_id',            '=', 'firm_profiles.id')
            ->whereBetween('applications.interview_date', [$windowStart, $windowEnd])
            ->whereNull('applications.reminder_1h_sent_at')
            ->whereNotIn('applications.student_interview_response', ['Rejected'])
            ->whereNotIn('applications.recruiter_status', ['Interview Rejected', 'Rejected', 'Cancelled'])
            ->select(
                'applications.id',
                'applications.student_id',
                'applications.interview_date',
                'applications.interview_mode',
                'applications.interview_note',
                'users.email  as student_email',
                'users.name   as student_name',
                'firm_profiles.firm_name',
                'jobs.title   as job_title'
            )
            ->get();

        if ($applications->isEmpty()) {
            return;
        }

        $purpose = EmailPurpose::INTERVIEW_REMINDER_1H;
        $sender  = EmailSenderResolver::resolve($purpose);
        $base    = config('app.frontend_url', 'https://startyourstory.in');

        foreach ($applications as $app) {
            // Reset $log each iteration so a previous iteration's $log
            // cannot leak into this iteration's catch block.
            $log = null;

            try {
                $interviewDate = date('D, d M Y \a\t h:i A', strtotime($app->interview_date));
                $viewUrl       = "{$base}/my-applications";

                $mailable = new InterviewReminder1HourMail(
                    $app->student_name,
                    $app->firm_name,
                    $app->job_title,
                    $interviewDate,
                    $app->interview_mode,
                    $app->interview_note,
                    $viewUrl
                );
                $mailable->from = [['address' => $sender['address'], 'name' => $sender['name']]];

                $log = EmailLog::create([
                    'recipient_email' => $app->student_email,
                    'recipient_type'  => 'student',
                    'email_purpose'   => $purpose->value,
                    'template_name'   => 'InterviewReminder1HourMail',
                    'sender_identity' => $purpose->senderKey(),
                    'subject'         => "Reminder: Your Interview Starts in 1 Hour — {$app->firm_name}",
                    'status'          => 'pending',
                ]);

                Mail::to($app->student_email)->send($mailable);

                $log->markSent();

                // Mark AFTER confirmed send. If send failed, the application
                // remains un-marked and the next run (every 30 min) will retry.
                DB::table('applications')
                    ->where('id', $app->id)
                    ->update(['reminder_1h_sent_at' => now()]);

                // Push notification (additive layer — queued; rides the same
                // duplicate protection as the email above).
                SendUserPushJob::dispatch(
                    (int) $app->student_id,
                    "Interview in 1 hour — {$app->firm_name}",
                    "{$app->job_title} · {$interviewDate}",
                    '/my-applications',
                    [],
                    'interview_app_' . $app->id // replaces the 24h reminder for this interview
                );

            } catch (Throwable $e) {
                Log::error('1h interview reminder failed', [
                    'application_id' => $app->id,
                    'student_email'  => $app->student_email,
                    'error'          => $e->getMessage(),
                ]);

                if ($log !== null) {
                    $log->markFailed(mb_substr($e->getMessage(), 0, 500));
                }
            }
        }
    }
}
