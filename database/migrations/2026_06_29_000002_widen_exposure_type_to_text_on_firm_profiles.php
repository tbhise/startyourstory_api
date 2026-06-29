<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * firm_profiles.exposure_type stores a JSON ARRAY as text (e.g. ["overall",
 * "domain wise"]), written/read with json_encode/json_decode in FirmController.
 * It was VARCHAR(500), which can silently truncate a growing array into invalid
 * JSON (json_decode then yields null/[]), and throws "Data too long" under MySQL
 * STRICT mode.
 *
 * Immediate production fix: widen VARCHAR(500) -> TEXT to remove the cap with NO
 * query refactor. (The sibling student_profiles.exposure_type is already native
 * JSON; a later migration MAY converge firm_profiles to JSON too — intentionally
 * NOT done here, to avoid touching the LIKE/whereIn filters in this task.)
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('firm_profiles', 'exposure_type')) {
            DB::statement('ALTER TABLE `firm_profiles` MODIFY `exposure_type` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('firm_profiles', 'exposure_type')) {
            // NOTE: rows longer than 500 chars will be truncated on rollback.
            DB::statement('ALTER TABLE `firm_profiles` MODIFY `exposure_type` VARCHAR(500) NULL');
        }
    }
};
