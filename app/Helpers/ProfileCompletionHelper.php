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
        $lookingFor = strtolower(trim((string) ($f['looking_for'] ?? '')));
        $caStatus      = strtolower(trim((string) ($f['ca_status'] ?? '')));

        $basicInfoComplete       = !empty($f['city']);
        $preferredLocationExists = !empty($f['has_preferred_location']);

        // Derivation is owned by RegistrationTypeHelper (single source of truth).
        $registrationType = RegistrationTypeHelper::derive(
            $f['looking_for'] ?? null,
            $f['ca_status'] ?? null
        );

        $isProfileComplete = false;

        if ($lookingFor === 'articleship') {
            // Preferred location + resume are only shown for Inter-Both (Case A).
            $skipLocationAndResume = in_array($caStatus, [
                'inter-g2',
                'doing-articleship',
                'doing articleship',
                'inter-g1',
                'pursuing-inter',
                'foundation',
            ], true);

            $isProfileComplete = $basicInfoComplete && !empty($f['srn']);

            if (!$skipLocationAndResume) {
                $isProfileComplete = $isProfileComplete && $preferredLocationExists;
            }

            // Inter-Both (Case A) requires exposure, core domain and attempts.
            if ($registrationType === RegistrationTypeHelper::CONFIRM && !$skipLocationAndResume) {
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
        } elseif (in_array($lookingFor, ['semi-qualified', 'qualified'], true)) {
            $isProfileComplete = $basicInfoComplete
                && !empty($f['srn'])
                && $preferredLocationExists;
        } elseif ($lookingFor === 'creator') {
            $isProfileComplete =
                !empty($f['city']) &&
                !empty($f['qualification']) &&
                !empty($f['availability_status']) &&
                !empty(trim((string) ($f['why_should_hire_you'] ?? ''))) &&
                is_numeric($f['experience_years'] ?? null) &&
                !empty($f['has_preferred_categories']);
        }

        // Students who opted into creator also need creator fields done.
        if (!empty($f['is_creator_optin']) && $lookingFor !== 'creator') {
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
