<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Jobs\LogActivityJob;
use Illuminate\Support\Facades\Log;

/**
 * Central, reusable entry point for recording firm/student business activity.
 *
 * Call this AFTER the host operation has succeeded (e.g. after DB::commit) —
 * only real, completed actions belong in the activity log:
 *
 *   ActivityTracker::log(
 *       ActivityTracker::FIRM,
 *       $userId,
 *       ActivityType::JOB_POSTED,
 *       ['job_id' => $jobId, 'job_title' => $title],
 *   );
 *
 * The actual INSERT happens asynchronously in LogActivityJob (queue), so the
 * caller returns immediately. Dispatching is wrapped in try/catch so that even
 * a queue/connection failure can NEVER break the host operation — a job that
 * posted successfully must still succeed even if its activity row is lost.
 *
 * `actorId` is the acting account's users.id (a firm or student is a users row
 * with role 'firm' / 'student'). actorType mirrors that role; use ::FIRM /
 * ::STUDENT, or ::actorFromRole() to derive it from a user row's role.
 */
class ActivityTracker
{
    public const FIRM    = 'firm';
    public const STUDENT = 'student';

    /**
     * Queue one activity-log row. Never throws.
     *
     * @param string                $actorType  self::FIRM | self::STUDENT
     * @param int|string|null       $actorId    acting account's users.id
     * @param ActivityType|string   $actionType action key (enum preferred)
     * @param array<string,mixed>   $meta       small, action-specific payload
     */
    public static function log(
        string $actorType,
        int|string|null $actorId,
        ActivityType|string $actionType,
        array $meta = [],
    ): void {
        try {
            if ($actorId === null || (int) $actorId <= 0) {
                return; // nothing meaningful to attribute the action to
            }

            $action = $actionType instanceof ActivityType ? $actionType->value : $actionType;

            LogActivityJob::dispatch(
                $actorType,
                (int) $actorId,
                $action,
                $meta === [] ? null : $meta,
                now()->toDateTimeString(),
            );
        } catch (\Throwable $e) {
            // Logging must be completely non-blocking: never let a dispatch
            // failure surface to the caller / user.
            Log::warning('ActivityTracker::log dispatch failed: ' . $e->getMessage(), [
                'actor_type'  => $actorType,
                'action_type' => $actionType instanceof ActivityType ? $actionType->value : $actionType,
            ]);
        }
    }

    /**
     * Map a users.role ('firm' | 'student') to an actor type, or null if it is
     * neither (so callers can skip logging for unsupported roles).
     */
    public static function actorFromRole(?string $role): ?string
    {
        return match ($role) {
            'firm'    => self::FIRM,
            'student' => self::STUDENT,
            default   => null,
        };
    }
}
