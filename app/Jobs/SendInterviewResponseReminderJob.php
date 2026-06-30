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
 * Scheduled job — runs hourly.
 *
 * Reminds students who have an interview invitation still awaiting their
 * accept/reject. Reminders escalate off interview_invites.invited_at:
 *
 *     ≥ 24h  → reminder 1
 *     ≥ 72h  → reminder 2
 *     ≥ 120h → reminder 3 (final), then stop.
 *
 * The number of reminders already sent is persisted in
 * interview_invites.response_reminders_sent, so missed scheduler runs catch up
 * (never double-send) and a fully-reminded invite is skipped. Each reminder is
 * delivered in-app (NotificationHelper) AND by email (EmailNotificationService,
 * which queues + logs the send). Per-invite try/catch keeps one failure from
 * affecting the others; the counter is advanced only after a successful pass so
 * a failed iteration is retried on the next hourly run.
 */
class SendInterviewResponseReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** Elapsed-hours thresholds for reminders 1, 2, 3. */
    private const STAGE_HOURS = [24, 72, 120];

    public function handle(): void
    {
        $maxStages = count(self::STAGE_HOURS); // 3

        // Pending, active invites that still have at least one reminder left.
        $invites = DB::table('interview_invites as ii')
            ->join('users as u', 'u.id', '=', 'ii.student_id')
            ->join('firm_profiles as fp', 'fp.id', '=', 'ii.firm_id')
            ->where('ii.invite_status', 'pending')
            ->where('ii.active_flag', 1)
            ->whereNotNull('ii.invited_at')
            ->where('ii.response_reminders_sent', '<', $maxStages)
            ->where('u.is_deleted', false)
            ->select(
                'ii.id',
                'ii.invited_at',
                'ii.response_reminders_sent',
                'ii.student_id',
                'u.email as student_email',
                'u.name  as student_name',
                'fp.firm_name'
            )
            ->get();

        if ($invites->isEmpty()) {
            return;
        }

        $emailService = app(EmailNotificationService::class);
        $now = now();

        foreach ($invites as $invite) {
            try {
                $sent = (int) $invite->response_reminders_sent;

                // How many reminders SHOULD have been sent by now, based on age.
                // Computed from raw timestamps so it is independent of Carbon's
                // signed-vs-absolute diff behaviour across major versions.
                $hoursElapsed = ($now->timestamp - strtotime((string) $invite->invited_at)) / 3600;
                $due = 0;
                foreach (self::STAGE_HOURS as $threshold) {
                    if ($hoursElapsed >= $threshold) {
                        $due++;
                    }
                }

                // Nothing new owed yet (or already caught up).
                if ($due <= $sent) {
                    continue;
                }

                $nextStage = $sent + 1;          // the reminder we are about to send (1..3)
                $isFinal   = $nextStage >= $maxStages;

                // In-app notification (already failure-safe internally).
                NotificationHelper::create(
                    (int) $invite->student_id,
                    'Interview Response Pending',
                    "{$invite->firm_name} is waiting for your response to an interview invitation. "
                        . 'Please accept or reject the interview request.'
                );

                // Email (queued + logged via EmailNotificationService → DispatchMailJob).
                $emailService->sendInterviewResponseReminder(
                    $invite->student_email,
                    $invite->student_name,
                    $invite->firm_name,
                    $isFinal
                );

                // Advance to the stage we just reached (handles catch-up after
                // missed runs in a single step; never sends more than one per run).
                DB::table('interview_invites')
                    ->where('id', $invite->id)
                    ->update([
                        'response_reminders_sent'   => $nextStage,
                        'last_response_reminder_at' => $now,
                        'updated_at'                => $now,
                    ]);
            } catch (Throwable $e) {
                // Isolate the failure — other invites and all business flows continue.
                Log::error('Interview response reminder failed', [
                    'invite_id'     => $invite->id,
                    'student_email' => $invite->student_email,
                    'error'         => $e->getMessage(),
                ]);
            }
        }
    }
}
