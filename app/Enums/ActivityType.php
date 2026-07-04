<?php

namespace App\Enums;

/**
 * Canonical list of tracked business actions for the activity log.
 *
 * These are the ONLY actions recorded in `activity_logs`. Extend this enum to
 * track a new action — no schema change is required (action_type is a string
 * column). Each value is stored verbatim in activity_logs.action_type.
 *
 * @see \App\Services\ActivityTracker
 */
enum ActivityType: string
{
    // ── Firm actions ──────────────────────────────────────────────────────────
    case JOB_POSTED              = 'job_posted';
    case INTERVIEW_INVITE_SENT   = 'interview_invite_sent';
    case INTERVIEW_SCHEDULED     = 'interview_scheduled';
    case CONTENT_CREATION_POSTED = 'content_creation_posted';
    case SUBSCRIPTION_PURCHASED  = 'subscription_purchased';

    // ── Student actions ───────────────────────────────────────────────────────
    case JOB_APPLIED             = 'job_applied';
    case INTERVIEW_ACCEPTED      = 'interview_accepted';
    case CONTENT_SUBMITTED       = 'content_submitted';
    case WALLET_RECHARGED        = 'wallet_recharged';
}
