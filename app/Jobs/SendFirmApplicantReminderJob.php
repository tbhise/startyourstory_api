<?php

namespace App\Jobs;

use App\Helpers\NotificationHelper;
use App\Services\Notifications\EmailNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Scheduled job — runs daily.
 *
 * Reminds firms that one or more of their ACTIVE job posts have applicants still
 * awaiting review (applications.recruiter_status = 'Applied' — the untouched
 * default before the firm shortlists / rejects / requests an interview).
 *
 * Anti-spam: a job is only included when it has never been reminded, or was last
 * reminded more than COOLDOWN_DAYS ago (jobs.last_applicant_reminder_at). A firm
 * receives ONE email + ONE in-app notification summarising all its jobs needing
 * review, and the included jobs are stamped so they won't recur until the
 * cooldown elapses. Per-firm try/catch isolates failures from each other and
 * from all business flows; jobs are stamped only after a successful pass.
 */
class SendFirmApplicantReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** Don't re-remind about the same job within this many days. */
    private const COOLDOWN_DAYS = 3;

    public function handle(): void
    {
        $now      = now();
        $cooldown = $now->copy()->subDays(self::COOLDOWN_DAYS);

        // Active jobs with ≥1 pending-review application, off cooldown.
        $rows = DB::table('jobs as j')
            ->join('firm_profiles as fp', 'fp.id', '=', 'j.firm_id')
            ->join('users as u', 'u.id', '=', 'fp.user_id')
            ->join('applications as a', function ($join) {
                $join->on('a.job_id', '=', 'j.id')
                     ->where('a.recruiter_status', '=', 'Applied');
            })
            ->where('j.is_active', 1)
            ->where('u.is_deleted', false)
            ->where(function ($q) use ($cooldown) {
                $q->whereNull('j.last_applicant_reminder_at')
                  ->orWhere('j.last_applicant_reminder_at', '<', $cooldown);
            })
            ->groupBy('j.id', 'j.title', 'fp.firm_name', 'u.id', 'u.email')
            ->select(
                'j.id     as job_id',
                'j.title  as job_title',
                'fp.firm_name',
                'u.id     as firm_user_id',
                'u.email  as firm_email',
                DB::raw('COUNT(a.id) as pending_count')
            )
            ->get();

        if ($rows->isEmpty()) {
            return;
        }

        $emailService = app(EmailNotificationService::class);

        // One reminder per firm, summarising all of that firm's pending jobs.
        foreach ($rows->groupBy('firm_user_id') as $firmUserId => $firmJobs) {
            try {
                $first   = $firmJobs->first();
                $jobList = $firmJobs->map(fn ($r) => [
                    'title' => $r->job_title,
                    'count' => (int) $r->pending_count,
                ])->values()->all();

                $total   = (int) $firmJobs->sum('pending_count');
                $jobCount = count($jobList);

                $message = $jobCount === 1
                    ? "Your job posting \"{$jobList[0]['title']}\" has {$total} "
                        . ($total === 1 ? 'applicant' : 'applicants') . ' waiting for review.'
                    : "You have {$total} applicants waiting for review across {$jobCount} job postings.";

                // In-app notification (failure-safe internally).
                NotificationHelper::create((int) $firmUserId, 'Applicants Awaiting Review', $message);

                // Push notification (additive layer — queued, failure-safe).
                SendUserPushJob::dispatch(
                    (int) $firmUserId,
                    $total . ($total === 1 ? ' applicant is' : ' applicants are') . ' awaiting your review',
                    $message,
                    '/firm-applications'
                );

                // Email (queued + logged via EmailNotificationService → DispatchMailJob).
                $emailService->sendFirmApplicantReminder(
                    $first->firm_email,
                    $first->firm_name,
                    $jobList,
                    $total
                );

                // Stamp every included job so it stays quiet until the cooldown elapses.
                DB::table('jobs')
                    ->whereIn('id', $firmJobs->pluck('job_id')->all())
                    ->update(['last_applicant_reminder_at' => $now, 'updated_at' => $now]);
            } catch (Throwable $e) {
                Log::error('Firm applicant reminder failed', [
                    'firm_user_id' => $firmUserId,
                    'error'        => $e->getMessage(),
                ]);
            }
        }
    }
}
