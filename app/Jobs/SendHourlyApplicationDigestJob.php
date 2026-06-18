<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Scheduled job — runs every hour.
 * Finds firms with un-notified applications and dispatches one
 * SendApplicationDigestJob per firm. Each worker job handles its own
 * marking and email send atomically.
 */
class SendHourlyApplicationDigestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        // Find all firm_profile IDs that have at least one un-notified application.
        $firms = DB::table('firm_profiles')
            ->join('jobs',         'firm_profiles.id', '=', 'jobs.firm_id')
            ->join('applications', 'jobs.id',          '=', 'applications.job_id')
            ->join('users',        'firm_profiles.user_id', '=', 'users.id')
            ->whereNull('applications.digest_notified_at')
            ->where('users.is_deleted', false)
            ->select(
                'firm_profiles.id   as firm_id',
                'firm_profiles.firm_name',
                'users.email        as firm_email'
            )
            ->distinct()
            ->get();

        if ($firms->isEmpty()) {
            return;
        }

        foreach ($firms as $firm) {
            SendApplicationDigestJob::dispatch(
                (int) $firm->firm_id,
                $firm->firm_name,
                $firm->firm_email
            );
        }
    }
}
