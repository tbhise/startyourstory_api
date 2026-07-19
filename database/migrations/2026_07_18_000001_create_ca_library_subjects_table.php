<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Lives in the separate ca_library database (see config/database.php).
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->create('ca_library_subjects', function (Blueprint $table) {
            $table->id();
            $table->string('course', 50);
            // NULL for CA Foundation (it has no groups).
            $table->string('group', 20)->nullable();
            $table->string('name', 150);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['course', 'group'], 'idx_cls_course_group');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->dropIfExists('ca_library_subjects');
    }
};
