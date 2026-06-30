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
            DB::table('activity_logs')->insert([
                'actor_type'  => $this->actorType,
                'actor_id'    => $this->actorId,
                'action_type' => $this->actionType,
                'meta'        => $this->meta !== null ? json_encode($this->meta) : null,
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

    public function failed(Throwable $exception): void
    {
        Log::warning('LogActivityJob exhausted retries: ' . $exception->getMessage(), [
            'actor_type'  => $this->actorType,
            'actor_id'    => $this->actorId,
            'action_type' => $this->actionType,
        ]);
    }
}
