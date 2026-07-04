<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Async writer for a single activity_logs row.
 *
 * Dispatched (never constructed-and-run inline) by ActivityTracker AFTER the
 * host business operation has succeeded, so logging stays off the request's
 * critical path. The job is intentionally tiny: one INSERT, no side effects.
 *
 * Failure is contained here — handle() is wrapped so a write error is logged
 * and swallowed rather than retried forever, and failed() is a final backstop.
 * A lost activity row must never escalate into anything the user can see.
 */
class LogActivityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    public function __construct(
        public string $actorType,
        public int    $actorId,
        public string $actionType,
        public ?array $meta = null,
        public ?string $createdAt = null,
    ) {}

    public function handle(): void
    {
        try {
            $meta = $this->enrichMeta($this->meta);

            DB::table('activity_logs')->insert([
                'actor_type'  => $this->actorType,
                'actor_id'    => $this->actorId,
                'action_type' => $this->actionType,
                'meta'        => $meta !== null ? json_encode($meta) : null,
                'created_at'  => $this->createdAt ?? now(),
            ]);
        } catch (Throwable $e) {
            // Swallow — an activity row is best-effort. Record once for ops and stop.
            Log::warning('LogActivityJob@handle failed: ' . $e->getMessage(), [
                'actor_type'  => $this->actorType,
                'actor_id'    => $this->actorId,
                'action_type' => $this->actionType,
            ]);
        }
    }

    /**
     * Resolve display names for the entity IDs referenced in meta, so the admin
     * tracker can render "Devesh Mishra" / "ABC & Co" instead of "Candidate #112".
     *
     * Meta ID conventions (shared by every ActivityTracker call site):
     *   student_id → users.id            ⇒ adds student_name
     *   firm_id    → firm_profiles.id    ⇒ adds firm_name
     *   job_id     → jobs.id             ⇒ adds job_title
     *
     * Names are frozen into the row at write time so history stays accurate if
     * an entity is later renamed. Callers may pre-fill a name key to skip its
     * lookup. Runs in the queue worker (never on the request path) and any
     * lookup failure falls back to the original meta — IDs still render fine.
     */
    private function enrichMeta(?array $meta): ?array
    {
        if ($meta === null) {
            return null;
        }

        try {
            if (!empty($meta['student_id']) && !isset($meta['student_name'])) {
                $name = DB::table('users')->where('id', $meta['student_id'])->value('name');
                if ($name !== null) {
                    $meta['student_name'] = $name;
                }
            }
            if (!empty($meta['firm_id']) && !isset($meta['firm_name'])) {
                $name = DB::table('firm_profiles')->where('id', $meta['firm_id'])->value('firm_name');
                if ($name !== null) {
                    $meta['firm_name'] = $name;
                }
            }
            if (!empty($meta['job_id']) && !isset($meta['job_title'])) {
                $title = DB::table('jobs')->where('id', $meta['job_id'])->value('title');
                if ($title !== null) {
                    $meta['job_title'] = $title;
                }
            }
        } catch (Throwable $e) {
            Log::warning('LogActivityJob meta enrichment skipped: ' . $e->getMessage(), [
                'action_type' => $this->actionType,
            ]);
        }

        return $meta;
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('LogActivityJob exhausted retries: ' . $exception->getMessage(), [
            'actor_type'  => $this->actorType,
            'actor_id'    => $this->actorId,
            'action_type' => $this->actionType,
        ]);
    }
}
