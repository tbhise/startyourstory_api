<?php

namespace App\Jobs;

use App\Enums\EmailPurpose;
use App\Mail\ApplicationDigestMail;
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
 * Queued worker for one firm's hourly application digest.
 *
 * Queries un-notified applications at execution time (safe for retries).
 * Sends the email synchronously within this job so we can mark
 * digest_notified_at ONLY after a confirmed successful send.
 * This guarantees no application is lost from future digests on failure.
 */
class SendApplicationDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int   $tries  = 3;
    public array $backoff = [60, 300];

    public function __construct(
        public readonly int    $firmProfileId,
        public readonly string $firmName,
        public readonly string $firmEmail
    ) {}

    public function handle(): void
    {
        // Re-query at execution time so retries always see fresh, un-notified data.
        $applications = DB::table('applications')
            ->join('jobs',  'applications.job_id',    '=', 'jobs.id')
            ->join('users', 'applications.student_id', '=', 'users.id')
            ->where('jobs.firm_id', $this->firmProfileId)
            ->whereNull('applications.digest_notified_at')
            ->select(
                'applications.id',
                'users.name    as candidate_name',
                'jobs.title    as job_title',
                'applications.applied_at'
            )
            ->orderBy('applications.applied_at')
            ->get();

        if ($applications->isEmpty()) {
            return;
        }

        $applicationIds = $applications->pluck('id')->toArray();

        $formatted = $applications->map(fn ($a) => [
            'candidate_name' => $a->candidate_name,
            'job_title'      => $a->job_title,
            'applied_at'     => date('d M Y, h:i A', strtotime($a->applied_at)),
        ])->toArray();

        $count   = count($formatted);
        $noun    = $count === 1 ? 'New Application' : 'New Applications';
        $base    = config('app.frontend_url', 'https://startyourstory.in');
        $viewUrl = "{$base}/firm/applications";

        $mailable = new ApplicationDigestMail($this->firmName, $formatted, $viewUrl);

        $purpose = EmailPurpose::APPLICATION_DIGEST;
        $sender  = EmailSenderResolver::resolve($purpose);
        $mailable->from = [['address' => $sender['address'], 'name' => $sender['name']]];

        $log = EmailLog::create([
            'recipient_email' => $this->firmEmail,
            'recipient_type'  => 'firm',
            'email_purpose'   => $purpose->value,
            'template_name'   => 'ApplicationDigestMail',
            'sender_identity' => $purpose->senderKey(),
            'subject'         => "{$count} {$noun} Received — Start Your Story",
            'status'          => 'pending',
        ]);

        // Send synchronously inside this queued job.
        // If this throws, the job retries and applications remain un-notified.
        Mail::to($this->firmEmail)->send($mailable);

        $log->markSent();

        // Mark only after confirmed send so retries can re-include these applications.
        DB::table('applications')
            ->whereIn('id', $applicationIds)
            ->update(['digest_notified_at' => now()]);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendApplicationDigestJob permanently failed', [
            'firm_id'   => $this->firmProfileId,
            'firm_name' => $this->firmName,
            'error'     => $exception->getMessage(),
        ]);
    }
}
