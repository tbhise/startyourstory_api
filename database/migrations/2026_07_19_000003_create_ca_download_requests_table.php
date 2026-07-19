<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ca_library database. One row per student+material — repeat requests update
// the existing row instead of duplicating it ("Download Again").
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->create('ca_download_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('study_material_id');
            // pending (awaiting email verification) → ready (downloadable)
            $table->string('status', 20)->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'study_material_id'], 'uq_cadr_student_material');
            $table->index('study_material_id', 'idx_cadr_material');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->dropIfExists('ca_download_requests');
    }
};
