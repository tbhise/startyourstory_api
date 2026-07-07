<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Employment Status feature — link the reported organization to a
     * registered firm when one was picked from the FirmSelector dropdown
     * (searchFirms API → firm_profiles.id). NULL when the student typed a
     * custom organization name, mirroring the student_profiles
     * current_firm_id / current_firm_name convention.
     */
    public function up(): void
    {
        Schema::table('student_employment_history', function (Blueprint $table) {
            $table->unsignedBigInteger('firm_id')->nullable()->after('user_id');
            $table->index('firm_id', 'idx_seh_firm');
        });
    }

    public function down(): void
    {
        Schema::table('student_employment_history', function (Blueprint $table) {
            $table->dropIndex('idx_seh_firm');
            $table->dropColumn('firm_id');
        });
    }
};
