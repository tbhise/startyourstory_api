<?php

namespace App\Services\Campaign;

use App\Enums\EmailPurpose;
use App\Mail\ReEngagementMail;
use App\Models\Campaign;
use App\Models\EmailLog;
use App\Services\Email\EmailSenderResolver;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use InvalidArgumentException;
use Throwable;

/**
 * Re-engagement campaign engine.
 *
 * Single source of truth for eligibility + sending, shared by the admin Campaign API
 * and the `mail:reengagement` CLI command. Replaces the logic that used to live inside
 * the console command.
 *
 * Reuses the existing mail stack unchanged: ReEngagementMail, the emails.reengagement
 * view, EmailLog + click tracking, and EmailSenderResolver.
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

    /* ------------------------------------------------------------------ */
    /*  Filters                                                            */
    /* ------------------------------------------------------------------ */

    /**
     * Validate + normalise an incoming filter payload. Throws on invalid input so
     * both the controller (→ 422) and the command (→ error) can surface the message.
     *
     * @return array{target_type:string,verification_status:string,profile_completion_status:string}
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

        return [
            'target_type'               => $target,
            'verification_status'        => $verification,
            'profile_completion_status'  => $profile,
        ];
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
     * The most recent campaign with the SAME filter set executed in the last 24h,
     * or null. Powers the duplicate guard.
     */
    public function recentDuplicate(array $filters): ?Campaign
    {
        $f = $this->normalizeFilters($filters);

        return Campaign::query()
            ->where('campaign_type', 'reengagement')
            ->where('target_type', $f['target_type'])
            ->where('verification_status', $f['verification_status'])
            ->where('profile_completion_status', $f['profile_completion_status'])
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
     */
    public function createCampaign(
        array $filters,
        string $initiatedFrom,
        ?int $adminId = null,
        ?string $name = null
    ): Campaign {
        $f = $this->normalizeFilters($filters);

        return Campaign::create([
            'campaign_type'              => 'reengagement',
            'campaign_name'              => $name ?: $this->defaultName($f),
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
     * Send one campaign email: log row → signed click URL → ReEngagementMail.
     * Returns true on success. A single bad recipient never aborts the run.
     */
    private function sendToRecipient(object $row, Campaign $campaign): bool
    {
        $userType    = $campaign->target_type; // the campaign targets exactly one type
        $isVerified  = !is_null($row->email_verified_at);
        $isCompleted = (bool) $row->profile_completed;
        $subject     = $this->subjectFor($userType, $isVerified, $isCompleted);

        $log = EmailLog::create([
            'campaign_id'     => $campaign->id,
            'recipient_email' => $row->email,
            'recipient_type'  => $userType === 'firm' ? 'firm' : 'student',
            'email_purpose'   => EmailPurpose::REENGAGEMENT->value,
            'template_name'   => 'ReEngagementMail',
            'sender_identity' => EmailPurpose::REENGAGEMENT->senderKey(),
            'subject'         => $subject,
            'status'          => 'pending',
        ]);

        try {
            $trackingUrl = URL::signedRoute('email.click', ['emailLog' => $log->id]);

            $mailable = new ReEngagementMail(
                $row->name ?: 'there',
                $userType,
                $isVerified,
                $isCompleted,
                $subject,
                $trackingUrl
            );
            $sender = EmailSenderResolver::resolve(EmailPurpose::REENGAGEMENT);
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
     * the CTA points at plain /login (untracked).
     */
    public function sendTest(string $email, array $filters): void
    {
        $f = $this->normalizeFilters($filters);

        $userType  = $f['target_type'];
        $verified  = $f['verification_status'] !== 'unverified';
        $completed = $f['profile_completion_status'] === 'completed';
        $subject   = '[TEST] ' . $this->subjectFor($userType, $verified, $completed);

        $loginUrl = rtrim(config('app.frontend_url', 'https://startyourstory.in'), '/') . '/login';

        $mailable = new ReEngagementMail('there', $userType, $verified, $completed, $subject, $loginUrl);
        $sender = EmailSenderResolver::resolve(EmailPurpose::REENGAGEMENT);
        $mailable->from = [['address' => $sender['address'], 'name' => $sender['name']]];

        Mail::to($email)->send($mailable);
    }

    /* ------------------------------------------------------------------ */
    /*  Helpers                                                            */
    /* ------------------------------------------------------------------ */

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
    private function defaultName(array $f): string
    {
        $type = ucfirst($f['target_type']);
        $bits = [];
        if ($f['verification_status'] !== 'all') {
            $bits[] = $f['verification_status'];
        }
        if ($f['profile_completion_status'] !== 'all') {
            $bits[] = $f['profile_completion_status'] . ' profile';
        }
        $suffix = $bits ? ' (' . implode(', ', $bits) . ')' : '';

        return "Re-engagement — {$type}{$suffix} — " . now()->format('d M Y H:i');
    }
}
