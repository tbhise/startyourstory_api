<?php

namespace App\Services\Marketing;

use App\Jobs\DispatchMailJob;
use App\Models\EmailLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Throwable;

/**
 * Marketing send engine for the `marketing_contacts` audience.
 *
 * Holds ALL the logic behind `marketing:send` (the command only parses options
 * and prints): template resolution, audience query, limit, dry run, test mode,
 * queueing and the `last_emailed_at` stamp.
 *
 * Nothing here is a new mail pipeline — every email goes out the existing way:
 *   email_logs row  →  DispatchMailJob (queue)  →  EmailSenderResolver by
 *   purpose  →  Mail::send  →  markSent()/markFailed()
 * with the signed `email.click` / `email.open` routes providing the same click
 * and open tracking the admin campaigns use. The log row is created before the
 * Mailable because those signed URLs need its id — the same ordering
 * ReEngagementCampaignService uses.
 *
 * The Mailable is never named here: MarketingTemplateRegistry resolves the
 * template key, so adding a future marketing email needs no change in this file.
 *
 * Safety notes:
 *  - One bad contact never aborts a run: invalid addresses are skipped and
 *    exceptions are caught, logged and counted.
 *  - The audience is streamed with lazyById (bounded memory) when unlimited.
 *  - `last_emailed_at` is stamped when the email is QUEUED (delivery outcome
 *    lives in email_logs.status, written later by the queue worker).
 */
class MarketingMailer
{
    /** Rows pulled per chunk while streaming the full audience. */
    private const CHUNK = 500;

    /** Sample contacts returned by a dry run. */
    private const SAMPLE_LIMIT = 10;

    /**
     * Queue a marketing template to every active contact.
     *
     * Sends are PACED: the Nth email is queued with an N × $delaySeconds delay, so
     * the queue's own `available_at` column spreads delivery instead of the worker
     * firing the whole list at SMTP in one burst. The command returns immediately —
     * the spacing lives in the queue, so it survives a worker restart.
     *
     * @param  callable(string $status, object $contact, string $note):void|null $progress
     * @return array{found:int,queued:int,skipped:int,failed:int}
     */
    public function send(
        string $templateKey,
        ?int $limit = null,
        ?callable $progress = null,
        int $delaySeconds = 0,
        bool $onlyNotEmailed = false
    ): array {
        MarketingTemplateRegistry::get($templateKey); // fail fast on a bad key

        $stats = ['found' => 0, 'queued' => 0, 'skipped' => 0, 'failed' => 0];
        $slot  = 0;

        foreach ($this->contacts($limit, $onlyNotEmailed) as $contact) {
            $stats['found']++;

            if (empty($contact->email) || !filter_var($contact->email, FILTER_VALIDATE_EMAIL)) {
                $stats['skipped']++;
                if ($progress) $progress('skipped', $contact, 'invalid email');
                continue;
            }

            try {
                // Only successfully queued recipients consume a time slot, so a run
                // with skipped rows still paces the mail that actually goes out.
                $logId = $this->queueOne($templateKey, $contact, false, $delaySeconds * $slot);
                $slot++;

                DB::table('marketing_contacts')
                    ->where('id', $contact->id)
                    ->update(['last_emailed_at' => now(), 'updated_at' => now()]);

                $stats['queued']++;
                if ($progress) $progress('queued', $contact, "email_log #{$logId}");
            } catch (Throwable $e) {
                $stats['failed']++;
                Log::warning('Marketing email queue failed for contact', [
                    'template' => $templateKey,
                    'email'    => $contact->email,
                    'error'    => $e->getMessage(),
                ]);
                if ($progress) $progress('failed', $contact, mb_substr($e->getMessage(), 0, 120));
            }
        }

        return $stats;
    }

    /** How many contacts a send would target, before --limit. */
    public function activeCount(): int
    {
        return $this->baseQuery()->count();
    }

    /**
     * Report what a send WOULD do. Queues nothing, writes nothing.
     *
     * @return array{template:string,subject:string,active_count:int,would_send:int,sample:array<int,array<string,mixed>>}
     */
    public function dryRun(string $templateKey, ?int $limit = null): array
    {
        $template = MarketingTemplateRegistry::get($templateKey);

        $active = $this->activeCount();

        $sample = $this->baseQuery()
            ->orderBy('id')
            ->limit(min(self::SAMPLE_LIMIT, $limit ?? self::SAMPLE_LIMIT))
            ->get()
            ->map(fn ($c) => [
                'id'              => $c->id,
                'firm_name'       => $c->firm_name,
                'email'           => $c->email,
                'last_emailed_at' => $c->last_emailed_at ?? '—',
            ])
            ->all();

        return [
            'template'     => $template['label'],
            'subject'      => MarketingTemplateRegistry::subjectFor($templateKey),
            'active_count' => $active,
            'would_send'   => $limit !== null ? min($limit, $active) : $active,
            'sample'       => $sample,
        ];
    }

    /**
     * Send ONE email to an arbitrary address for QA. Touches no contact row.
     *
     * The subject is prefixed [TEST], the CTA points at the template's real
     * (untracked) destination and no open pixel is embedded, so a test send
     * never pollutes click/open metrics. It still writes an email_logs row and
     * still goes through DispatchMailJob — run inline (dispatchSync) so the
     * outcome is known before the command returns.
     *
     * @return int email_logs id
     */
    public function sendTest(string $templateKey, string $email): int
    {
        MarketingTemplateRegistry::get($templateKey);

        return $this->queueOne(
            $templateKey,
            (object) ['id' => null, 'firm_name' => 'Firm X LLP', 'email' => $email],
            test: true
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Internals                                                          */
    /* ------------------------------------------------------------------ */

    /**
     * Active contacts with a usable address.
     *
     * $onlyNotEmailed narrows it to contacts never emailed before, which is what
     * makes `--limit` safe to run daily: without it, a second run would re-send to
     * the same first N contacts.
     */
    private function baseQuery(bool $onlyNotEmailed = false): \Illuminate\Database\Query\Builder
    {
        $q = DB::table('marketing_contacts')
            ->where('status', 'active')
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->select(['id', 'firm_name', 'email', 'last_emailed_at']);

        if ($onlyNotEmailed) {
            $q->whereNull('last_emailed_at');
        }

        return $q;
    }

    /**
     * The audience to send to. Streamed in chunks when unlimited; a --limit run
     * is a plain bounded fetch (lazyById manages its own LIMIT, so the two
     * cannot be combined).
     *
     * @return iterable<object>
     */
    private function contacts(?int $limit, bool $onlyNotEmailed = false): iterable
    {
        if ($limit !== null) {
            return $this->baseQuery($onlyNotEmailed)->orderBy('id')->limit($limit)->get();
        }

        return $this->baseQuery($onlyNotEmailed)->lazyById(self::CHUNK, 'id');
    }

    /**
     * Log row → signed tracking URLs → Mailable → queue. Returns the log id.
     */
    private function queueOne(string $templateKey, object $contact, bool $test = false, int $delaySeconds = 0): int
    {
        $template = MarketingTemplateRegistry::get($templateKey);
        $purpose  = $template['purpose'];
        $subject  = ($test ? '[TEST] ' : '') . MarketingTemplateRegistry::subjectFor($templateKey);

        $log = EmailLog::create([
            'recipient_email' => $contact->email,
            'recipient_type'  => 'firm', // marketing contacts are firms — with no account here
            'email_purpose'   => $purpose->value,
            'template_name'   => MarketingTemplateRegistry::templateNameFor($templateKey),
            'sender_identity' => $purpose->senderKey(),
            'subject'         => $subject,
            'status'          => 'pending',
        ]);

        $mailable = MarketingTemplateRegistry::make($templateKey, [
            'firm_name' => trim((string) ($contact->firm_name ?? '')) ?: 'there',
            'subject'   => $subject,
            // Test sends stay out of the metrics: real CTA, no pixel.
            'cta_url' => $test
                ? MarketingTemplateRegistry::ctaUrlFor($templateKey)
                : URL::signedRoute('email.click', ['emailLog' => $log->id]),
            'open_pixel_url' => $test
                ? null
                : URL::signedRoute('email.open', ['emailLog' => $log->id]),
        ]);

        if ($test) {
            DispatchMailJob::dispatchSync($contact->email, $mailable, $log->id);
        } else {
            $job = DispatchMailJob::dispatch($contact->email, $mailable, $log->id);
            if ($delaySeconds > 0) {
                $job->delay(now()->addSeconds($delaySeconds));
            }
        }

        return $log->id;
    }
}
