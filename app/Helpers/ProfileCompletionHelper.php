<?php

namespace App\Helpers;

/**
 * Single source of truth for the student "profile_completed" calculation.
 *
 * The completion rules were previously inlined inside
 * UserController@updateProfile. They are now centralised here so that any flow
 * that changes a field which affects completion (the full profile save AND the
 * lightweight career-status update) computes the exact same result — no
 * duplicated logic, no drift.
 *
 * The caller normalises its own data source (HTTP request vs. stored DB row)
 * into the flat array below; this class only owns the DECISION.
 *
 * Expected keys (all optional — missing values are treated as empty):
 *   looking_for              (string) raw looking_for value
 *   ca_status                (string) raw ca_status value
 *   city                     (string)
 *   srn                      (string)
 *   has_preferred_location   (bool)   at least one preferred location present
 *   exposure_filled          (bool)   exposure_type has at least one entry
 *   core_department          (string)
 *   attempts                 (string)
 *   current_firm_name        (string)
 *   is_creator_optin         (bool)   student ticked the creator opt-in
 *   qualification            (string)
 *   availability_status      (string)
 *   why_should_hire_you      (string)
 *   experience_years         (mixed)  numeric check applied
 *   has_preferred_categories (bool)   at least one preferred category present
 */
class ProfileCompletionHelper
{
    public static function isComplete(array $f): bool
    {
        // Raw looking_for is compared as-is for the articleship / semi-qualified /
        // qualified / creator branches (mirrors the original === comparisons);
        // a normalised copy is used only where the original normalised it.
        $lookingForRaw = $f['looking_for'] ?? null;
        $lookingFor    = strtolower(trim((string) ($f['looking_for'] ?? '')));
        $caStatus      = $f['ca_status'] ?? null;

        $basicInfoComplete       = !empty($f['city']);
        $preferredLocationExists = !empty($f['has_preferred_location']);

        // registration_type derivation (mirrors UserController@updateProfile).
        $registrationType = 'provisional';
        if (in_array($lookingFor, ['semi-qualified', 'qualified'])) {
            $registrationType = 'confirm';
        } elseif ($lookingFor === 'articleship') {
            $cs = strtolower(trim((string) $caStatus));
            if (in_array($cs, [
                'inter-both',
                'inter both groups passed',
                'doing-articleship',
                'doing articleship',
            ])) {
                $registrationType = 'confirm';
            }
        }

        $isProfileComplete = false;

        if ($lookingForRaw === 'articleship') {
            // Preferred location + resume are only shown for Inter-Both (Case A).
            $skipLocationAndResume = in_array($caStatus, [
                'inter-g2',
                'doing-articleship',
                'doing articleship',
                'inter-g1',
                'pursuing-inter',
                'foundation',
            ]);

            $isProfileComplete = $basicInfoComplete && !empty($f['srn']);

            if (!$skipLocationAndResume) {
                $isProfileComplete = $isProfileComplete && $preferredLocationExists;
            }

            // Inter-Both (Case A) requires exposure, core domain and attempts.
            if ($registrationType === 'confirm' && !$skipLocationAndResume) {
                $isProfileComplete = $isProfileComplete
                    && !empty($f['exposure_filled'])
                    && !empty($f['core_department'])
                    && !empty($f['attempts']);
            }

            // Doing-Articleship (Case B) also collects the current articleship firm.
            if (in_array($caStatus, ['doing-articleship', 'doing articleship'])) {
                $isProfileComplete = $isProfileComplete && !empty($f['current_firm_name']);
            }
        } elseif (in_array($lookingFor, ['doing-articleship', 'already_doing_articleship'])) {
            // Case B — basic info, srn and current articleship firm only.
            $isProfileComplete = $basicInfoComplete
                && !empty($f['srn'])
                && !empty($f['current_firm_name']);
        } elseif (in_array($lookingForRaw, ['semi-qualified', 'qualified'])) {
            $isProfileComplete = $basicInfoComplete
                && !empty($f['srn'])
                && $preferredLocationExists;
        } elseif ($lookingForRaw === 'creator') {
            $isProfileComplete =
                !empty($f['city']) &&
                !empty($f['qualification']) &&
                !empty($f['availability_status']) &&
                !empty(trim((string) ($f['why_should_hire_you'] ?? ''))) &&
                is_numeric($f['experience_years'] ?? null) &&
                !empty($f['has_preferred_categories']);
        }

        // Students who opted into creator also need creator fields done.
        if (!empty($f['is_creator_optin']) && $lookingForRaw !== 'creator') {
            $isCreatorFieldsComplete =
                !empty($f['qualification']) &&
                !empty($f['availability_status']) &&
                !empty(trim((string) ($f['why_should_hire_you'] ?? ''))) &&
                is_numeric($f['experience_years'] ?? null) &&
                !empty($f['has_preferred_categories']);
            $isProfileComplete = $isProfileComplete && $isCreatorFieldsComplete;
        }

        return (bool) $isProfileComplete;
    }
}
