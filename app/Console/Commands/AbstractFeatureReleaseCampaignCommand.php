<?php

namespace App\Console\Commands;

use App\Services\Campaign\CampaignEmailService;
use App\Services\Campaign\CampaignRecipientService;
use Illuminate\Console\Command;

/**
 * Shared skeleton for the feature-release campaign commands.
 *
 * Deliberately THIN: parses CLI options, resolves recipients via
 * CampaignRecipientService, hands them to CampaignEmailService, prints
 * progress/summary. No SQL, no payloads, no Mail calls here — abstract
 * classes are also skipped by Artisan's command discovery, so only the
 * concrete student/firm subclasses register.
 *
 * Safety: a FULL-audience run (no --limit / --email) asks for confirmation;
 * pass --force for schedulers / non-interactive shells.
 */
abstract class AbstractFeatureReleaseCampaignCommand extends Command
{
    /** users.role this campaign targets: 'student' | 'firm'. */
    abstract protected function role(): string;

    /** CampaignEmailService::* campaign key. */
    abstract protected function campaign(): string;

    /** Plural audience label for output ("students" / "firms"). */
    abstract protected function audienceLabel(): string;

    public function handle(
        CampaignRecipientService $recipients,
        CampaignEmailService $campaigns,
    ): int {
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;
        $email = $this->option('email');

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("[ERROR] '{$email}' is not a valid email address.");
            return self::FAILURE;
        }

        // ── Resolve the recipient set ────────────────────────────────────────
        if ($email) {
            $set   = $recipients->byEmail($this->role(), $email);
            $total = 1;
            $this->info("[INFO] Single-recipient run: {$email}");
        } else {
            $total = $recipients->count($this->role());
            $this->info("[INFO] Found {$total} {$this->audienceLabel()}");
            if ($limit !== null) {
                $this->info("[INFO] --limit={$limit} → queueing for the first {$limit} only");
            }
            $set = $recipients->byRole($this->role(), $limit);
        }

        // ── Dry run: report + samples, send nothing ─────────────────────────
        if ($this->option('dry-run')) {
            $this->line('[DRY-RUN] No emails will be queued.');
            $sample = $email
                ? iterator_to_array($set)
                : $recipients->sample($this->role(), min(5, $limit ?? 5));
            $this->table(
                ['user id', 'name', 'email'],
                collect($sample)->map(fn ($r) => [$r->id ?? '—', $r->name ?? '—', $r->email])->all()
            );
            $wouldQueue = $email ? 1 : ($limit !== null ? min($limit, $total) : $total);
            $this->info("[DONE] Dry run — {$wouldQueue} email(s) would be queued.");
            return self::SUCCESS;
        }

        // ── Full-audience guard ──────────────────────────────────────────────
        if (!$email && $limit === null && !$this->option('force')) {
            if (!$this->confirm("Queue this campaign for ALL {$total} {$this->audienceLabel()}?")) {
                $this->warn('[ABORTED] Nothing queued. Use --force to skip this prompt.');
                return self::FAILURE;
            }
        }

        // ── Queue via the campaign service (standard mail pipeline) ─────────
        $stats = $campaigns->send($this->campaign(), $set, function (string $status, object $r, string $note) {
            $who = '#' . ($r->id ?? '?') . ' (' . $r->email . ')';
            match ($status) {
                'queued'  => $this->line("[SUCCESS] Queued {$who} — {$note}"),
                'skipped' => $this->warn("[SKIP] {$who} — {$note}"),
                'failed'  => $this->error("[FAIL] {$who} — {$note}"),
            };
        });

        $this->newLine();
        $this->info(sprintf(
            '[DONE] Campaign queued for %d %s — found: %d, queued: %d, skipped: %d, failed: %d',
            $stats['queued'],
            $this->audienceLabel(),
            $stats['found'],
            $stats['queued'],
            $stats['skipped'],
            $stats['failed'],
        ));
        $this->line('Delivery happens via the queue worker (DispatchMailJob); check email_logs for per-recipient status.');

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
