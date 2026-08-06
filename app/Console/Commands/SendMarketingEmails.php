<?php

namespace App\Console\Commands;

use App\Services\Marketing\MarketingMailer;
use App\Services\Marketing\MarketingTemplateRegistry;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Throwable;

/**
 * Send a marketing email to the `marketing_contacts` audience.
 *
 *   php artisan marketing:send technology-solutions
 *   php artisan marketing:send technology-solutions --test=my@email.com
 *   php artisan marketing:send technology-solutions --limit=50
 *   php artisan marketing:send technology-solutions --dry-run
 *
 * Deliberately THIN: it validates options, hands the template key to
 * MarketingMailer and prints whatever the progress callback reports. No SQL, no
 * Mail classes, no copy — the template key is resolved through
 * MarketingTemplateRegistry, so a future marketing email needs NO change here.
 *
 * Safety: a full-audience run (no --limit / --test) asks for confirmation; pass
 * --force for schedulers and non-interactive shells (same guard the
 * feature-release campaign commands use).
 */
class SendMarketingEmails extends Command
{
    protected $signature = 'marketing:send
        {template : Template key (e.g. technology-solutions)}
        {--test=        : Send one [TEST] email to this address; contacts are not touched}
        {--limit=       : Send to the first N active contacts only}
        {--not-emailed  : Only contacts never emailed before (makes --limit safe to repeat daily)}
        {--delay=       : Seconds between emails (default 20). --delay=0 sends as fast as SMTP allows}
        {--dry-run      : Report what would be sent; queues nothing}
        {--force        : Skip the full-audience confirmation prompt}';

    protected $description = 'Queue a marketing email (from MarketingTemplateRegistry) to the active marketing_contacts audience.';

    /** Seconds between emails when --delay is not given (= 180/hour). */
    private const DEFAULT_DELAY = 20;

    public function handle(MarketingMailer $mailer): int
    {
        $template = (string) $this->argument('template');
        $test     = $this->option('test');
        $limit    = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $fresh    = (bool) $this->option('not-emailed');
        // Paced by DEFAULT: an unthrottled 400-address run is what gets a domain
        // rate-limited or spam-foldered. --delay=0 is an explicit opt-out.
        $delay = $this->option('delay') !== null ? max(0, (int) $this->option('delay')) : self::DEFAULT_DELAY;

        // ── Validate the template key up front ───────────────────────────────
        try {
            $meta = MarketingTemplateRegistry::get($template);
        } catch (InvalidArgumentException $e) {
            $this->error("[ERROR] {$e->getMessage()}");
            return self::FAILURE;
        }

        $this->info("[INFO] Template: {$meta['label']}  ({$template})");
        $this->line('       Subject:  ' . MarketingTemplateRegistry::subjectFor($template));

        // ── Test send: one address, nothing else touched ─────────────────────
        if ($test !== null) {
            if (!filter_var($test, FILTER_VALIDATE_EMAIL)) {
                $this->error("[ERROR] '{$test}' is not a valid email address.");
                return self::FAILURE;
            }

            try {
                $logId = $mailer->sendTest($template, $test);
            } catch (Throwable $e) {
                $this->error("[FAIL] Test send to {$test} failed — {$e->getMessage()}");
                return self::FAILURE;
            }

            $this->info("[DONE] Test email sent to {$test} (email_log #{$logId}).");
            if (config('mail.default') === 'log') {
                $this->warn("[NOTE] The default mailer is 'log' — the email was written to storage/logs/laravel.log, NOT delivered.");
            }

            return self::SUCCESS;
        }

        // ── Dry run: report + sample, queue nothing ──────────────────────────
        if ($this->option('dry-run')) {
            $report = $mailer->dryRun($template, $limit, $fresh);

            $this->line('[DRY-RUN] No emails will be queued.');
            $this->info("[INFO] Audience: {$report['active_count']}" . ($fresh ? ' never-emailed contact(s)' : ' active contact(s)'));
            if ($limit !== null) {
                $this->info("[INFO] --limit={$limit} → would send to the first {$limit}");
            }
            $this->table(
                ['id', 'firm name', 'email', 'last emailed'],
                array_map(fn ($c) => array_values($c), $report['sample'])
            );
            $this->info("[DONE] Dry run — {$report['would_send']} email(s) would be queued.");
            $this->line('       Pacing:   ' . $this->pacing($report['would_send'], $delay));

            return self::SUCCESS;
        }

        // ── Full-audience guard ──────────────────────────────────────────────
        $total = $mailer->activeCount($fresh);

        if ($total === 0) {
            $this->warn($fresh
                ? '[ABORTED] Every active contact has already been emailed. Drop --not-emailed to send again.'
                : '[ABORTED] No active marketing contacts. Import some with `marketing:import` first.');
            return self::SUCCESS;
        }

        $planned = $limit !== null ? min($limit, $total) : $total;
        $this->line('       Pacing:   ' . $this->pacing($planned, $delay));

        if ($limit === null && !$this->option('force')) {
            if (!$this->confirm("Queue '{$meta['label']}' for ALL {$total} contacts?")) {
                $this->warn('[ABORTED] Nothing queued. Use --force to skip this prompt.');
                return self::FAILURE;
            }
        }

        // ── Queue ────────────────────────────────────────────────────────────
        $stats = $mailer->send($template, $limit, function (string $status, object $c, string $note) {
            $who = '#' . ($c->id ?? '?') . ' (' . $c->email . ')';
            match ($status) {
                'queued'  => $this->line("[SUCCESS] Queued {$who} — {$note}"),
                'skipped' => $this->warn("[SKIP] {$who} — {$note}"),
                'failed'  => $this->error("[FAIL] {$who} — {$note}"),
            };
        }, $delay, $fresh);

        $this->newLine();
        $this->info(sprintf(
            '[DONE] found: %d, queued: %d, skipped: %d, failed: %d',
            $stats['found'],
            $stats['queued'],
            $stats['skipped'],
            $stats['failed'],
        ));
        $this->line('Delivery happens via the queue worker (DispatchMailJob); check email_logs for per-recipient status.');
        if ($delay > 0) {
            $this->line("The worker must stay up for the whole window — the {$delay}s spacing lives in the queue (available_at), not in this command.");
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    /** Human summary of how long a paced run takes, and at what hourly rate. */
    private function pacing(int $count, int $delay): string
    {
        if ($delay === 0) {
            return "NO delay — all {$count} email(s) hit SMTP as fast as the worker can go (spam/rate-limit risk).";
        }

        $seconds = max(0, $count - 1) * $delay;
        $perHour = (int) floor(3600 / $delay);
        $window  = $seconds >= 3600
            ? sprintf('%dh %dm', intdiv($seconds, 3600), intdiv($seconds % 3600, 60))
            : sprintf('%dm', (int) ceil($seconds / 60));

        return "{$delay}s apart (~{$perHour}/hour) → {$count} email(s) over ~{$window}.";
    }
}
