<?php

namespace App\Console\Commands;

use App\Enums\EmailPurpose;
use App\Jobs\DispatchMailJob;
use App\Mail\ReEngagementMail;
use App\Models\EmailLog;
use App\Services\Email\EmailSenderResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Re-engagement email campaign.
 *
 * Sweeps the whole user base in a single run and emails every user who has NOT
 * completed their profile, auto-detecting each user's segment (student / firm /
 * creator × verified / unverified) and sending the matching copy + CTA set.
 *
 * Backend-only marketing tool, triggered manually from the terminal. Reuses the
 * existing mail stack (ReEngagementMail + emails.reengagement view + EmailLog
 * tracking + the database queue when --queue is passed).
 *
 *   php artisan mail:reengagement                 # send to ALL eligible users
 *   php artisan mail:reengagement --dry-run       # preview, send nothing
 *   php artisan mail:reengagement --type=firm     # narrow to one type
 *   php artisan mail:reengagement --verified=0    # narrow to unverified only
 *   php artisan mail:reengagement --limit=1       # send to a single recipient
 *   php artisan mail:reengagement --sleep=2       # pause 2s between sends
 *   php artisan mail:reengagement --queue         # hand off to DispatchMailJob
 */
class SendReEngagementEmails extends Command
{
    protected $signature = 'mail:reengagement
        {--type=    : Restrict to a single user type: student|firm|creator}
        {--verified= : Restrict to a verification state: 0 (unverified) or 1 (verified)}
        {--dry-run  : List eligible recipients and counts without sending}
        {--limit=   : Cap the number of emails sent}
        {--sleep=0  : Seconds to pause between sends (SMTP rate-limit safety)}
        {--queue    : Dispatch via the database queue instead of sending synchronously}
        {--test     : Redirect ALL emails to TEST_EMAIL instead of the real recipients}';

    protected $description = 'Send re-engagement emails to users with incomplete profiles (all segments in one sweep).';

    private const TYPES = ['student', 'firm', 'creator'];

    /**
     * Test inbox used when --test is passed. All emails are delivered here
     * instead of the real recipients. Change this address as needed.
     */
    private const TEST_EMAIL = 'tusharbhise908@gmail.com';

    public function handle(): int
    {
        $type     = $this->option('type');
        $verified = $this->option('verified');
        $dryRun   = (bool) $this->option('dry-run');
        $useQueue = (bool) $this->option('queue');
        $useTest  = (bool) $this->option('test');
        $limit    = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $sleep    = (int) $this->option('sleep');

        // --- Validate options -------------------------------------------------
        if ($type !== null && !in_array($type, self::TYPES, true)) {
            $this->error("Invalid --type \"{$type}\". Allowed: " . implode(', ', self::TYPES) . '.');
            return self::FAILURE;
        }
        if ($verified !== null && !in_array($verified, ['0', '1'], true)) {
            $this->error('Invalid --verified value. Use 0 (unverified) or 1 (verified).');
            return self::FAILURE;
        }

        // --- Build the single eligibility query -------------------------------
        $query = DB::table('users')
            ->leftJoin('student_profiles', 'student_profiles.user_id', '=', 'users.id')
            ->where('users.is_deleted', 0)
            ->where('users.profile_completed', 0)
            ->whereNotNull('users.email')
            ->where('users.email', '<>', '')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                'users.email_verified_at',
                'student_profiles.looking_for',
            ]);

        if ($verified === '1') {
            $query->whereNotNull('users.email_verified_at');
        } elseif ($verified === '0') {
            $query->whereNull('users.email_verified_at');
        }

        if ($type === 'firm') {
            $query->where('users.role', 'firm');
        } elseif ($type === 'creator') {
            $query->where('users.role', 'student')->where('student_profiles.looking_for', 'creator');
        } elseif ($type === 'student') {
            $query->where('users.role', 'student')->where(function ($q) {
                $q->whereNull('student_profiles.looking_for')
                  ->orWhere('student_profiles.looking_for', '<>', 'creator');
            });
        }

        $query->orderBy('users.id');
        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        // --- Fetch + filter for valid emails ----------------------------------
        $rows = $query->get()->filter(
            fn ($row) => filter_var($row->email, FILTER_VALIDATE_EMAIL) !== false
        )->values();

        $total = $rows->count();
        $this->info("Total eligible users: {$total}");

        if ($useTest && !$dryRun) {
            $this->warn('TEST MODE: all emails will be delivered to ' . self::TEST_EMAIL . ' (real recipients are NOT emailed).');
        }

        if ($total === 0) {
            $this->warn('Nothing to do.');
            return self::SUCCESS;
        }

        // --- Dry run ----------------------------------------------------------
        if ($dryRun) {
            $bySegment = [];
            foreach ($rows as $row) {
                $seg = $this->segmentOf($row);
                $bySegment[$seg] = ($bySegment[$seg] ?? 0) + 1;
                $this->line("  [{$seg}] {$row->email}");
            }
            $this->newLine();
            $this->info('Per-segment breakdown:');
            foreach ($bySegment as $seg => $count) {
                $this->line("  {$seg}: {$count}");
            }
            $this->newLine();
            $this->comment('Dry run — no emails were sent.');
            return self::SUCCESS;
        }

        // --- Send -------------------------------------------------------------
        $sender  = EmailSenderResolver::resolve(EmailPurpose::REENGAGEMENT);
        $success = [];
        $failed  = [];

        foreach ($rows as $row) {
            $userType = $this->typeOf($row);
            $isVerified = !is_null($row->email_verified_at);
            $segment  = ($isVerified ? 'verified' : 'unverified') . " {$userType}";
            $deliverTo = $useTest ? self::TEST_EMAIL : $row->email;

            $label = $useTest ? "{$deliverTo} (test → {$row->email}, {$segment})" : "{$row->email} ({$segment})";
            $this->output->write("Sending email to {$label} ... ");

            try {
                $cta      = $this->ctaUrls($userType);
                $subject  = $this->subjectFor($userType, $isVerified);
                $mailable = new ReEngagementMail(
                    $row->name ?: 'there',
                    $userType,
                    $isVerified,
                    $subject,
                    $cta
                );

                $log = EmailLog::create([
                    'recipient_email' => $row->email,
                    'recipient_type'  => $userType === 'firm' ? 'firm' : 'student',
                    'email_purpose'   => EmailPurpose::REENGAGEMENT->value,
                    'template_name'   => 'ReEngagementMail',
                    'sender_identity' => EmailPurpose::REENGAGEMENT->senderKey(),
                    'subject'         => $subject,
                    'status'          => 'pending',
                ]);

                if ($useQueue) {
                    DispatchMailJob::dispatch($deliverTo, $mailable, $log->id);
                    $this->line('<info>Queued</info>');
                } else {
                    $mailable->from = [['address' => $sender['address'], 'name' => $sender['name']]];
                    Mail::to($deliverTo)->send($mailable);
                    $log->markSent();
                    $this->line('<info>Sent</info>');
                }

                $success[$segment] = ($success[$segment] ?? 0) + 1;
                sleep(1); // brief pause to avoid overwhelming the SMTP server
            } catch (Throwable $e) {
                if (isset($log)) {
                    $log->markFailed(mb_substr($e->getMessage(), 0, 500));
                }
                $failed[$segment] = ($failed[$segment] ?? 0) + 1;
                $this->line("<fg=red>Failed: {$row->email} — {$e->getMessage()}</>");
            }

            if ($sleep > 0) {
                sleep($sleep);
            }
        }

        // --- Summary ----------------------------------------------------------
        $this->newLine();
        $this->info('Done.');
        $totalSuccess = array_sum($success);
        $totalFailed  = array_sum($failed);
        $verb = $useQueue ? 'Queued' : 'Sent';

        $this->line("Per-segment {$verb}:");
        foreach ($success as $segment => $count) {
            $this->line("  {$segment}: {$count}");
        }
        if ($totalFailed > 0) {
            $this->line('Per-segment Failed:');
            foreach ($failed as $segment => $count) {
                $this->line("  {$segment}: {$count}");
            }
        }
        $this->newLine();
        $this->info("{$verb}: {$totalSuccess}   Failed: {$totalFailed}   Total: {$total}");

        return $totalFailed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** Derive the user type for a fetched row. */
    private function typeOf(object $row): string
    {
        if ($row->role === 'firm') {
            return 'firm';
        }
        return $row->looking_for === 'creator' ? 'creator' : 'student';
    }

    /** Human-readable "verified/unverified type" label for dry-run grouping. */
    private function segmentOf(object $row): string
    {
        $state = is_null($row->email_verified_at) ? 'unverified' : 'verified';
        return "{$state} {$this->typeOf($row)}";
    }

    /** Frontend CTA URLs for a given user type. */
    private function ctaUrls(string $userType): array
    {
        $base = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/');

        return [
            'login'   => "{$base}/login",
            'profile' => $userType === 'firm' ? "{$base}/firm-profile" : "{$base}/profile",
            'verify'  => "{$base}/verify-email",
        ];
    }

    /** Subject line per segment. */
    private function subjectFor(string $userType, bool $verified): string
    {
        return match ($userType) {
            'firm' => $verified
                ? 'Complete your firm profile and start hiring — Start Your Story'
                : 'Verify your email to start hiring — Start Your Story',
            'creator' => $verified
                ? 'Complete your creator profile to get discovered — Start Your Story'
                : 'Verify your email to get discovered — Start Your Story',
            default => $verified
                ? 'Complete your profile and start applying — Start Your Story'
                : 'Verify your email to start applying — Start Your Story',
        };
    }
}
