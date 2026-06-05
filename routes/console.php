<?php

use App\Jobs\SendHourlyApplicationDigestJob;
use App\Jobs\SendInterviewReminder1HourJob;
use App\Jobs\SendInterviewReminder24HoursJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use App\Helpers\WalletHelper;
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
