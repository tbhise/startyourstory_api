<?php

namespace App\Helpers;

/**
 * Single source of truth for deriving student_profiles.registration_type.
 *
 * The derivation was previously duplicated inside UserController@updateProfile
 * and ProfileCompletionHelper (and updateCareerStatus never recomputed it at
 * all, leaving stale values). Every flow that changes looking_for or ca_status
 * must call derive() and persist the result — no inline copies anywhere.
 *
 * Business rules:
 *   looking_for = semi-qualified | qualified          → confirm
 *   looking_for = already_doing_articleship           → confirm
 *   looking_for = articleship AND ca_status in
 *     {inter-both, inter both groups passed,
 *      doing-articleship, doing articleship}          → confirm
 *   everything else                                   → provisional
 *
 * The frontend never supplies this value; the backend always derives it.
 * All comparisons are trimmed + lowercased.
 */
class RegistrationTypeHelper
{
    public const CONFIRM     = 'confirm';
    public const PROVISIONAL = 'provisional';

    /** ca_status values that make an articleship registration "confirm". */
    public const CONFIRM_CA_STATUSES = [
        'inter-both',
        'inter both groups passed',
        'doing-articleship',
        'doing articleship',
    ];

    public static function derive(?string $lookingFor, ?string $caStatus): string
    {
        $lf = strtolower(trim((string) $lookingFor));

        if (in_array($lf, ['semi-qualified', 'qualified', 'already_doing_articleship'], true)) {
            return self::CONFIRM;
        }

        if ($lf === 'articleship') {
            $cs = strtolower(trim((string) $caStatus));
            if (in_array($cs, self::CONFIRM_CA_STATUSES, true)) {
                return self::CONFIRM;
            }
        }

        return self::PROVISIONAL;
    }
}
