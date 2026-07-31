<?php

namespace App\Services\Campaign;

use App\Models\Campaign;
use App\Models\EmailLog;
use App\Services\Email\EmailSenderResolver;
use Illuminate\Database\Query\Builder;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Throwable;

/**
 * Campaign engine (originally re-engagement only, now template-driven).
 *
 * Single source of truth for eligibility + sending, shared by the admin Campaign API
 * and the `mail:reengagement` CLI command. Replaces the logic that used to live inside
 * the console command.
 *
 * The Mailable is no longer hardcoded: the campaign's template key (stored in
 * `campaigns.campaign_type`) is resolved through CampaignTemplateRegistry, so a new
 * built-in campaign email needs no change here. Defaults to 'reengagement', which
 * keeps every existing caller (CLI command, AudienceMatcher) behaving identically.
 *
 * Reuses the existing mail stack unchanged: the campaign Mailables, EmailLog +
 * click/open tracking, and EmailSenderResolver.
 *
 * Safety notes:
 *  - The eligibility query runs against `users` ONLY (its PK is unique), with the
 *    creator/student split expressed via whereExists subqueries instead of a join.
 *    `student_profiles.user_id` has no unique constraint, so a join could multiply
 *    user rows and make lazyById skip/duplicate records — the subquery form avoids that.
 *  - Sending is chunked via lazyById (bounded memory) and runs inside ProcessCampaignJob
 *    on the queue, never in the request cycle. No sleep().
 */
class ReEngagementCampaignService
{
    /** How many recipients to pull per chunk while streaming the eligible set. */
    private const CHUNK = 500;

    /** A few sample recipients returned by a dry run. */
    private const SAMPLE_LIMIT = 8;

    private const TARGET_TYPES  = ['student', 'creator', 'firm'];
    private const VERIFICATIONS = ['all', 'verified', 'unverified'];
    private const PROFILES       = ['all', 'completed', 'incomplete'];
    private const PLANS          = ['all', 'premium', 'free'];

    /* ------------------------------------------------------------------ */
    /*  Filters                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Validate + normalise an incoming filter payload. Throws on invalid input so
     * both the controller (→ 422) and the command (→ error) can surface the message.
     *
     * `plan` is optional and defaults to 'all', so pre-existing callers that pass only
     * the three original keys are unaffected.
     *
     * @return array{target_type:string,verification_status:string,profile_completion_status:string,plan:string}
     */
    public function normalizeFilters(array $in): array
    {
        $target = $in['target_type'] ?? null;
        if (!in_array($target, self::TARGET_TYPES, true)) {
            throw new InvalidArgumentException('target_type must be one of: ' . implode(', ', self::TARGET_TYPES) . '.');
        }
        $verification = $in['verification_status'] ?? 'all';
        if (!in_array($verification, self::VERIFICATIONS, true)) {
            throw new InvalidArgumentException('verification_status must be one of: ' . implode(', ', self::VERIFICATIONS) . '.');
        }
        $profile = $in['profile_completion_status'] ?? 'all';
        if (!in_array($profile, self::PROFILES, true)) {
            throw new InvalidArgumentException('profile_completion_status must be one of: ' . implode(', ', self::PROFILES) . '.');
        }

        $plan = $in['plan'] ?? 'all';
        if (!in_array($plan, self::PLANS, true)) {
            throw new InvalidArgumentException('plan must be one of: ' . implode(', ', self::PLANS) . '.');
        }

        return [
            'target_type'               => $target,
            'verification_status'        => $verification,
            'profile_completion_status'  => $profile,
            'plan'                       => $plan,
        ];
    }

    /**
     * Validate the requested template key against the chosen target type.
     * Defaults to the re-engagement template so existing callers are unchanged.
     *
     * @throws InvalidArgumentException
     */
    public function normalizeTemplate(array $in, string $targetType): string
    {
        $key = (string) ($in['template_key'] ?? CampaignTemplateRegistry::REENGAGEMENT);
        CampaignTemplateRegistry::assertSupports($key, $targetType);

        return $key;
    }

    /* ------------------------------------------------------------------ */
    /*  Eligibility                                                        */
    /* ------------------------------------------------------------------ */

    /**
     * Build the eligible-users query for a (normalised) filter set.
     * users-only base + whereExists subqueries → exactly one row per user.
     */
    public function buildEligibilityQuery(array $filters): Builder
    {
        $f = $this->normalizeFilters($filters);

        $q = DB::table('users')
            ->where('users.is_deleted', 0)
            ->whereNotNull('users.email')
            ->where('users.email', '<>', '')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                'users.email_verified_at',
                'users.profile_completed',
            ]);

        // Verification state.
        if ($f['verification_status'] === 'verified') {
            $q->whereNotNull('users.email_verified_at');
        } elseif ($f['verification_status'] === 'unverified') {
            $q->whereNull('users.email_verified_at');
        }

        // Profile completion state.
        if ($f['profile_completion_status'] === 'completed') {
            $q->where('users.profile_completed', 1);
        } elseif ($f['profile_completion_status'] === 'incomplete') {
            $q->where('users.profile_completed', 0);
        }

        // Target type. A "creator" is a student user with a creator student_profile;
        // a plain "student" is a student user WITHOUT one.
        $hasCreatorProfile = function ($sub) {
            $sub->select(DB::raw(1))
                ->from('student_profiles')
                ->whereColumn('student_profiles.user_id', 'users.id')
                ->where('student_profiles.looking_for', 'creator');
        };

        if ($f['target_type'] === 'firm') {
            $q->where('users.role', 'firm');
        } elseif ($f['target_type'] === 'creator') {
            $q->where('users.role', 'student')->whereExists($hasCreatorProfile);
        } else { // student
            $q->where('users.role', 'student')->whereNotExists($hasCreatorProfile);
        }

        // Plan dimension. Premium is always DERIVED from an active, non-expired
        // subscription — never from the stale firm_profiles.is_premium flag — which
        // matches SubscriptionHelper::isPremiumFirm and Engagement\AudienceMatcher.
        if ($f['plan'] !== 'all') {
            $hasActiveSubscription = $f['target_type'] === 'firm'
                ? function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('firm_subscriptions')
                        ->join('firm_profiles', 'firm_profiles.id', '=', 'firm_subscriptions.firm_id')
                        ->whereColumn('firm_profiles.user_id', 'users.id')
                        ->whereNotNull('firm_subscriptions.plan')
                        ->where('firm_subscriptions.plan', '<>', 'free')
                        ->where('firm_subscriptions.status', 'active')
                        ->where(function ($w) {
                            $w->whereNull('firm_subscriptions.expires_at')
                              ->orWhere('firm_subscriptions.expires_at', '>', now());
                        });
                }
                : function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('student_subscriptions')
                        ->whereColumn('student_subscriptions.user_id', 'users.id')
                        ->where('student_subscriptions.status', 'active')
                        ->where(function ($w) {
                            $w->whereNull('student_subscriptions.expires_at')
                              ->orWhere('student_subscriptions.expires_at', '>', now());
                        });
                };

            if ($f['plan'] === 'premium') {
                $q->whereExists($hasActiveSubscription);
            } else { // free
                $q->whereNotExists($hasActiveSubscription);
            }
        }

        return $q;
    }

    /**
     * Count eligible users + return a small sample. Sends nothing.
     *
     * @return array{eligible_count:int,sample_users:array<int,array<string,mixed>>}
     */
    public function dryRun(array $filters): array
    {
        $query = $this->buildEligibilityQuery($filters);

        $eligible = (clone $query)->count();

        $sample = (clone $query)
            ->reorder('users.id')
            ->limit(self::SAMPLE_LIMIT)
            ->get()
            ->map(fn ($row) => [
                'name'      => $row->name,
                'email'     => $row->email,
                'verified'  => !is_null($row->email_verified_at),
                'completed' => (bool) $row->profile_completed,
            ])
            ->all();

        return ['eligible_count' => $eligible, 'sample_users' => $sample];
    }

    /**
     * The most recent campaign with the SAME template AND filter set executed in the
     * last 24h, or null. Powers the duplicate guard — scoped per template so sending
     * a different announcement to the same segment is never treated as a duplicate.
     * `plan` lives only in the filters JSON (no denormalised column), so it is matched
     * via a JSON path.
     */
    public function recentDuplicate(array $filters, string $templateKey = CampaignTemplateRegistry::REENGAGEMENT): ?Campaign
    {
        $f = $this->normalizeFilters($filters);

        return Campaign::query()
            ->where('campaign_type', $templateKey)
            ->where('target_type', $f['target_type'])
            ->where('verification_status', $f['verification_status'])
            ->where('profile_completion_status', $f['profile_completion_status'])
            // Campaigns created before the plan filter existed have no `plan` key;
            // treat those as 'all' so the guard still catches them.
            ->where(function ($q) use ($f) {
                $q->where('filters->plan', $f['plan']);
                if ($f['plan'] === 'all') {
                    $q->orWhereNull('filters->plan');
                }
            })
            ->where('created_at', '>=', now()->subDay())
            ->latest('id')
            ->first();
    }

    /* ------------------------------------------------------------------ */
    /*  Campaign lifecycle                                                 */
    /* ------------------------------------------------------------------ */

    /**
     * Create a pending campaign row (does NOT send — dispatch ProcessCampaignJob, or
     * call run() directly for --sync). eligible_count is snapshotted at creation.
     *
     * The chosen template key is persisted in `campaign_type`; `$subject` is the
     * admin's edited subject (null = use the template default / per-segment subject).
     */
    public function createCampaign(
        array $filters,
        string $initiatedFrom,
        ?int $adminId = null,
        ?string $name = null,
        string $templateKey = CampaignTemplateRegistry::REENGAGEMENT,
        ?string $subject = null
    ): Campaign {
        $f = $this->normalizeFilters($filters);
        CampaignTemplateRegistry::assertSupports($templateKey, $f['target_type']);

        return Campaign::create([
            'campaign_type'              => $templateKey,
            'campaign_name'              => $name ?: $this->defaultName($f, $templateKey),
            'subject'                    => $subject,
            'target_type'                => $f['target_type'],
            'verification_status'        => $f['verification_status'],
            'profile_completion_status'  => $f['profile_completion_status'],
            'filters'                    => $f,
            'eligible_count'             => $this->buildEligibilityQuery($f)->count(),
            'status'                     => Campaign::STATUS_PENDING,
            'initiated_from'             => $initiatedFrom,
            'executed_by_admin_id'       => $adminId,
        ]);
    }

    /**
     * Execute a campaign: stream the eligible set in chunks and send each email,
     * updating counters + status. Called from ProcessCampaignJob (queued) or inline
     * for --sync. Idempotent guard lives in the job.
     */
    public function run(Campaign $campaign): void
    {
        $campaign->update([
            'status'     => Campaign::STATUS_RUNNING,
            'started_at' => now(),
        ]);

        $sent = 0;
        $failed = 0;

        try {
            $this->buildEligibilityQuery($campaign->filters)
                ->lazyById(self::CHUNK, 'users.id', 'id')
                ->each(function ($row) use ($campaign, &$sent, &$failed) {
                    if ($this->sendToRecipient($row, $campaign)) {
                        $sent++;
                    } else {
                        $failed++;
                    }
                });

            $campaign->update([
                'status'       => Campaign::STATUS_COMPLETED,
                'sent_count'   => $sent,
                'failed_count' => $failed,
                'completed_at' => now(),
            ]);
        } catch (Throwable $e) {
            // Unexpected failure mid-run (not a single bad recipient — those are caught
            // per-row below). Persist whatever progress we made and mark failed.
            $campaign->update([
                'status'       => Campaign::STATUS_FAILED,
                'sent_count'   => $sent,
                'failed_count' => $failed,
                'completed_at' => now(),
            ]);
            Log::error('Campaign run failed', ['campaign_id' => $campaign->id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Send one campaign email: log row → signed click/open URLs → the template's
     * Mailable. Returns true on success. A single bad recipient never aborts the run.
     */
    private function sendToRecipient(object $row, Campaign $campaign): bool
    {
        $templateKey = $campaign->campaign_type;
        $template    = CampaignTemplateRegistry::get($templateKey);
        $purpose     = $template['purpose'];

        $userType    = $campaign->target_type; // the campaign targets exactly one type
        $isVerified  = !is_null($row->email_verified_at);
        $isCompleted = (bool) $row->profile_completed;
        $subject     = $this->subjectForCampaign($campaign, $isVerified, $isCompleted);

        $log = EmailLog::create([
            'campaign_id'     => $campaign->id,
            'recipient_email' => $row->email,
            'recipient_type'  => $userType === 'firm' ? 'firm' : 'student',
            'email_purpose'   => $purpose->value,
            'template_name'   => $template['template_name'],
            'sender_identity' => $purpose->senderKey(),
            'subject'         => $subject,
            'status'          => 'pending',
        ]);

        try {
            $mailable = CampaignTemplateRegistry::make($templateKey, [
                'name'           => $row->name ?: 'there',
                'user_type'      => $userType,
                'verified'       => $isVerified,
                'completed'      => $isCompleted,
                'subject'        => $subject,
                'cta_url'        => URL::signedRoute('email.click', ['emailLog' => $log->id]),
                'open_pixel_url' => URL::signedRoute('email.open', ['emailLog' => $log->id]),
            ]);
            $sender = EmailSenderResolver::resolve($purpose);
            $mailable->from = [['address' => $sender['address'], 'name' => $sender['name']]];

            Mail::to($row->email)->send($mailable);
            $log->markSent();
            return true;
        } catch (Throwable $e) {
            $log->markFailed(mb_substr($e->getMessage(), 0, 500));
            Log::warning('Campaign recipient send failed', [
                'campaign_id' => $campaign->id,
                'email'       => $row->email,
                'error'       => $e->getMessage(),
            ]);
            return false;
        }
    }

    /* ------------------------------------------------------------------ */
    /*  Test send (QA preview — no campaign row, no bulk)                  */
    /* ------------------------------------------------------------------ */

    /**
     * Send a single preview email to an arbitrary address using a representative
     * segment derived from the filters. Creates no campaign and no email_logs row;
     * the CTA points at the template's real (untracked) destination and no open
     * pixel is embedded, so test sends never pollute campaign metrics.
     */
    public function sendTest(
        string $email,
        array $filters,
        string $templateKey = CampaignTemplateRegistry::REENGAGEMENT,
        ?string $subject = null
    ): void {
        $mailable = $this->buildPreviewMailable($filters, $templateKey, $subject, testMode: true);

        $sender = EmailSenderResolver::resolve(CampaignTemplateRegistry::get($templateKey)['purpose']);
        $mailable->from = [['address' => $sender['address'], 'name' => $sender['name']]];

        Mail::to($email)->send($mailable);
    }

    /**
     * Render the exact email an admin is about to send, as an HTML string, without
     * sending anything. Same Mailable + Blade view as a real send.
     */
    public function renderPreview(
        array $filters,
        string $templateKey = CampaignTemplateRegistry::REENGAGEMENT,
        ?string $subject = null
    ): array {
        $mailable = $this->buildPreviewMailable($filters, $templateKey, $subject, testMode: false);

        return [
            'subject' => $this->previewSubject($filters, $templateKey, $subject),
            'html'    => $mailable->render(),
        ];
    }

    /**
     * Shared builder for the test send and the HTML preview: a representative
     * recipient for the chosen segment, untracked CTA, no open pixel.
     */
    private function buildPreviewMailable(
        array $filters,
        string $templateKey,
        ?string $subject,
        bool $testMode
    ): Mailable {
        $f = $this->normalizeFilters($filters);
        CampaignTemplateRegistry::assertSupports($templateKey, $f['target_type']);

        $line = $this->previewSubject($filters, $templateKey, $subject);

        return CampaignTemplateRegistry::make($templateKey, [
            'name'           => 'there',
            'user_type'      => $f['target_type'],
            'verified'       => $f['verification_status'] !== 'unverified',
            'completed'      => $f['profile_completion_status'] === 'completed',
            'subject'        => $testMode ? '[TEST] ' . $line : $line,
            'cta_url'        => CampaignTemplateRegistry::ctaUrlFor($templateKey),
            'open_pixel_url' => null,
        ]);
    }

    /** Subject a preview/test would carry: admin override → template default → per-segment. */
    private function previewSubject(array $filters, string $templateKey, ?string $subject): string
    {
        if ($subject !== null && trim($subject) !== '') {
            return trim($subject);
        }

        $default = CampaignTemplateRegistry::get($templateKey)['default_subject'];
        if ($default !== null) {
            return $default;
        }

        $f = $this->normalizeFilters($filters);

        return $this->subjectFor(
            $f['target_type'],
            $f['verification_status'] !== 'unverified',
            $f['profile_completion_status'] === 'completed'
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Subject for one recipient of a running campaign: the admin's saved override
     * wins, then the template's fixed default, then the per-segment line.
     */
    private function subjectForCampaign(Campaign $campaign, bool $verified, bool $completed): string
    {
        if (!empty($campaign->subject)) {
            return $campaign->subject;
        }

        $default = CampaignTemplateRegistry::get($campaign->campaign_type)['default_subject'];

        return $default ?? $this->subjectFor($campaign->target_type, $verified, $completed);
    }

    /** Subject line per segment (target type × lifecycle state). Lifted from the command. */
    public function subjectFor(string $userType, bool $verified, bool $completed): string
    {
        $state = !$verified ? 'unverified' : ($completed ? 'completed' : 'incomplete');

        return match ($userType) {
            'firm' => match ($state) {
                'unverified' => 'Verify your email to start hiring',
                'incomplete' => 'Complete your firm profile and start hiring',
                default      => 'Start posting jobs and reaching candidates',
            },
            'creator' => match ($state) {
                'unverified' => 'Verify your email to get discovered',
                'incomplete' => 'Complete your creator profile to get discovered',
                default      => 'Get discovered for new content projects',
            },
            default => match ($state) {
                'unverified' => 'Verify your email to start applying',
                'incomplete' => 'Complete your profile and start applying',
                default      => 'New jobs and firms are waiting for you',
            },
        };
    }

    /** Auto-generated human label when the caller doesn't supply one. */
    private function defaultName(array $f, string $templateKey = CampaignTemplateRegistry::REENGAGEMENT): string
    {
        $type = ucfirst($f['target_type']);
        $bits = [];
        if ($f['verification_status'] !== 'all') {
            $bits[] = $f['verification_status'];
        }
        if ($f['profile_completion_status'] !== 'all') {
            $bits[] = $f['profile_completion_status'] . ' profile';
        }
        if ($f['plan'] !== 'all') {
            $bits[] = $f['plan'];
        }
        $suffix = $bits ? ' (' . implode(', ', $bits) . ')' : '';
        $label  = CampaignTemplateRegistry::get($templateKey)['label'];

        return "{$label} — {$type}{$suffix} — " . now()->format('d M Y H:i');
    }
}
