<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lives in the separate ca_library database (see config/database.php).
// The PDF itself is stored on the public disk; only path + metadata here.
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->create('ca_library_study_materials', function (Blueprint $table) {
            $table->id();
            $table->string('course', 50);
            // NULL for CA Foundation; "Both Groups" allowed for cross-group material.
            $table->string('group', 20)->nullable();
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('resource_type_id');
            $table->string('exam_attempt', 30);
            $table->string('title', 255);
            $table->string('file_path', 500);
            $table->string('original_file_name', 255);
            $table->unsignedBigInteger('file_size'); // bytes
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['course', 'group'], 'idx_clm_course_group');
            $table->index('subject_id', 'idx_clm_subject');
            $table->index('resource_type_id', 'idx_clm_type');
            $table->index('exam_attempt', 'idx_clm_attempt');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->dropIfExists('ca_library_study_materials');
    }
};
