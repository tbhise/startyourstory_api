<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Existing rows are all English material, so the column default backfills them.
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->table('ca_library_study_materials', function (Blueprint $table) {
            $table->string('medium', 20)->default('English')->after('exam_attempt');
            $table->index('medium', 'idx_clm_medium');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->table('ca_library_study_materials', function (Blueprint $table) {
            $table->dropIndex('idx_clm_medium');
            $table->dropColumn('medium');
        });
    }
};
