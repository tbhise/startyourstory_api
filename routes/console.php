<?php

use App\Jobs\AutoApproveEngagementsJob;
use App\Jobs\SendHourlyApplicationDigestJob;
use App\Jobs\SendInterviewReminder1HourJob;
use App\Jobs\SendInterviewReminder24HoursJob;
use App\Jobs\SendInterviewResponseReminderJob;
use App\Jobs\ExpirePendingInterviewConfirmationsJob;
use App\Jobs\SendFirmApplicantReminderJob;
use App\Jobs\SendUnreadDigestPushJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use App\Helpers\WalletHelper;
use App\Helpers\SysCoinHelper;
use App\Helpers\NotificationHelper;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Expire premium flags for firms whose subscription has lapsed
|--------------------------------------------------------------------------
| Runs daily. Finds any firm_profiles where is_premium = 1 but no active,
| non-expired subscription exists, then clears the flag and marks the
| firm_subscriptions row as expired.
*/
Schedule::call(function () {
    // Expire overdue subscriptions
    DB::table('firm_subscriptions')
        ->where('status', 'active')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->update(['status' => 'expired', 'updated_at' => now()]);

    // Clear is_premium for firms with no remaining active subscription
    DB::table('firm_profiles')
        ->where('is_premium', 1)
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
              ->from('firm_subscriptions')
              ->whereColumn('firm_subscriptions.firm_id', 'firm_profiles.id')
              ->where('firm_subscriptions.status', 'active')
              ->where(function ($inner) {
                  $inner->whereNull('firm_subscriptions.expires_at')
                        ->orWhere('firm_subscriptions.expires_at', '>', now());
              });
        })
        ->update(['is_premium' => 0, 'updated_at' => now()]);
})->dailyAt('01:00')->name('expire-premium-firms')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Auto-expire application holds older than 10 days with no recruiter action
|--------------------------------------------------------------------------
*/
Schedule::call(function () {
    $expiredHolds = DB::table('application_holds')
        ->where('status', 'held')
        ->where('expires_at', '<', now())
        ->get();

    foreach ($expiredHolds as $hold) {
        WalletHelper::release((int) $hold->application_id, 'auto_expired');

        NotificationHelper::create(
            $hold->user_id,
            'Application Expired',
            "Your application has expired after " . WalletHelper::HOLD_DAYS . " days. ₹{$hold->amount} has been returned to your wallet."
        );
    }

    // SYS Coin holds — same 10-day auto-expiry, coins returned to available.
    $expiredCoinHolds = DB::table('sys_coin_holds')
        ->where('status', 'held')
        ->where('expires_at', '<', now())
        ->get();

    foreach ($expiredCoinHolds as $hold) {
        SysCoinHelper::release((int) $hold->application_id, 'auto_expired');

        NotificationHelper::create(
            $hold->user_id,
            'Application Expired',
            "Your application has expired after " . SysCoinHelper::HOLD_DAYS . " days. {$hold->amount} SYS Coins have been returned."
        );
    }
})->hourly()->name('expire-application-holds')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Send 24-hour interview reminders
|--------------------------------------------------------------------------
| Runs every hour. Finds interviews due in ~24 hours and sends one reminder
| per confirmed/pending application. Duplicate protection via
| applications.reminder_24h_sent_at.
*/
Schedule::job(new SendInterviewReminder24HoursJob())
    ->hourly()
    ->name('send-interview-reminder-24h')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Send 1-hour interview reminders
|--------------------------------------------------------------------------
| Runs every 30 minutes. Finds interviews due in 45–90 minutes and sends
| one reminder per application. Duplicate protection via
| applications.reminder_1h_sent_at.
*/
Schedule::job(new SendInterviewReminder1HourJob())
    ->everyThirtyMinutes()
    ->name('send-interview-reminder-1h')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Auto-approve creator engagement submissions after 7 days
|--------------------------------------------------------------------------
| Prevents firms from downloading work and ghosting creators.
| If the latest submission remains unreviewed for > 7 days the engagement
| is automatically approved and both parties are notified.
*/
Schedule::job(new AutoApproveEngagementsJob())
    ->dailyAt('02:00')
    ->name('auto-approve-creator-engagements')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Transition approved creator engagements to payout_pending
|--------------------------------------------------------------------------
| Runs daily at 02:15 (after auto-approve) so that auto-approved
| engagements are also caught in the same cycle.
| - Reads current commission_percentage from platform_settings
| - Creates a creator_payouts record (gross, commission, net)
| - Moves engagement to 'payout_pending'
| - Notifies creator
*/
Schedule::call(function () {
    $commissionRate = (float) (DB::table('platform_settings')
        ->where('key', 'commission_percentage')
        ->value('value') ?? '10');

    $approved = DB::table('creator_engagements')
        ->where('status', 'approved')
        ->get(['id', 'creator_id', 'creator_requirement_id', 'accepted_bid_amount']);

    foreach ($approved as $eng) {
        // Skip if a payout record already exists (idempotency)
        $alreadyExists = DB::table('creator_payouts')
            ->where('engagement_id', $eng->id)
            ->exists();

        if (! $alreadyExists) {
            $gross      = (float) $eng->accepted_bid_amount;
            $commission = round($gross * $commissionRate / 100, 2);
            $net        = round($gross - $commission, 2);

            DB::table('creator_payouts')->insert([
                'engagement_id'     => $eng->id,
                'creator_id'        => $eng->creator_id,
                'gross_amount'      => $gross,
                'commission_rate'   => $commissionRate,
                'commission_amount' => $commission,
                'net_amount'        => $net,
                'status'            => 'pending',
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }

        DB::table('creator_engagements')->where('id', $eng->id)->update([
            'status'     => 'payout_pending',
            'updated_at' => now(),
        ]);

        DB::table('engagement_timeline')->insert([
            'engagement_id' => $eng->id,
            'user_id'       => null,
            'role'          => 'system',
            'event'         => 'payout_queued',
            'note'          => null,
            'meta'          => json_encode(['commission_rate' => $commissionRate]),
            'created_at'    => now(),
        ]);

        $title = DB::table('creator_projects')
            ->where('id', $eng->creator_requirement_id)
            ->value('title') ?? 'your project';

        $net = round((float) $eng->accepted_bid_amount * (1 - $commissionRate / 100), 2);

        DB::table('creator_marketplace_notifications')->insert([
            'user_id'    => $eng->creator_id,
            'type'       => 'payout_queued',
            'title'      => 'Payout queued',
            'body'       => "Your payout of ₹" . number_format($net, 2) . " for \"{$title}\" has been queued for processing.",
            'data'       => json_encode(['engagement_id' => (int) $eng->id]),
            'read_at'    => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
})->dailyAt('01:20')->name('queue-creator-payouts')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Send hourly application digest emails to firms
|--------------------------------------------------------------------------
| Runs every hour. Finds firms with un-notified applications (digest_notified_at
| IS NULL) and dispatches one SendApplicationDigestJob per firm. The worker
| job marks applications after a successful send to ensure each application
| appears in exactly one digest email.
*/
Schedule::job(new SendHourlyApplicationDigestJob())
    ->hourly()
    ->name('send-hourly-application-digest')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Finalize 30-day student account deletions
|--------------------------------------------------------------------------
| Runs daily. Permanently deactivates (soft-delete) any student whose
| 30-day grace window has elapsed and who has NOT logged back in (a login
| clears scheduled_deletion_at — see AuthController@login). Records are
| never physically removed; only is_deleted is set to TRUE.
| Scoped to role = 'student' so firm/admin accounts are never affected.
*/
Schedule::call(function () {
    DB::table('users')
        ->where('role', 'student')
        ->where('is_deleted', false)
        ->whereNotNull('scheduled_deletion_at')
        ->where('scheduled_deletion_at', '<=', now())
        ->update([
            'is_deleted'       => true,
            'api_token'        => null,
            'token_expires_at' => null,
            'updated_at'       => now(),
        ]);
})->dailyAt('03:00')->name('finalize-student-account-deletions')->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Remind students of a PENDING interview-invite response
|--------------------------------------------------------------------------
| Runs hourly. Sends escalating reminders (24h / 72h / 5 days after the invite)
| to students who have an active, still-pending interview invitation. Both
| in-app + email. Catch-up safe and never double-sends via
| interview_invites.response_reminders_sent. See SendInterviewResponseReminderJob.
*/
Schedule::job(new SendInterviewResponseReminderJob())
    ->hourly()
    ->name('send-interview-response-reminders')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Auto-expire scheduled interviews the student never confirmed (Phase 2)
|--------------------------------------------------------------------------
| Runs hourly. Expires 'scheduled' interviews (both invite + applications
| flows) whose student response is still pending past the configurable
| window (system_settings.interview_confirmation_timeout_days, default 5).
| Consumes NO interview credit — credit is only charged at confirmation.
| See ExpirePendingInterviewConfirmationsJob.
*/
Schedule::job(new ExpirePendingInterviewConfirmationsJob())
    ->hourly()
    ->name('expire-pending-interview-confirmations')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Remind firms of applicants AWAITING REVIEW on active jobs
|--------------------------------------------------------------------------
| Runs daily at 09:00. Sends one in-app + email reminder per firm summarising
| active jobs that have applications still in 'Applied' (un-reviewed) status.
| Anti-spam: a job won't recur within 3 days (jobs.last_applicant_reminder_at).
| See SendFirmApplicantReminderJob.
*/
Schedule::job(new SendFirmApplicantReminderJob())
    ->dailyAt('09:00')
    ->name('send-firm-applicant-reminders')
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Smart unread-notifications digest PUSH (students + firms)
|--------------------------------------------------------------------------
| Runs hourly; the job itself decides who (if anyone) gets a digest:
| unread > 0 AND inactive > 6h AND last digest > 8h ago AND unread count
| grew since the last digest AND 09:00–21:00 IST (max 2/day by construction).
| Push-only — no bell insert, no email. See SendUnreadDigestPushJob.
*/
Schedule::job(new SendUnreadDigestPushJob())
    ->hourly()
    ->name('send-unread-digest-push')
    ->withoutOverlapping();

// (2026-07-04) The unread-messages reminder EMAIL job was removed before ever
// shipping: final strategy is email ONLY on new-conversation requests
// (NewMessageRequestMail); replies notify via push alone.
