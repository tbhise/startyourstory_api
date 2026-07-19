<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ca_library database. Answer-sheet evaluation submissions. `amount` is the
// evaluation fee SNAPSHOTTED from Platform Settings at creation time — later
// fee changes never affect existing rows. Files live on the private disk.
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->create('ca_test_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('study_material_id');

            $table->string('answer_sheet_path', 500);
            $table->string('answer_sheet_original_name', 255);
            $table->unsignedBigInteger('answer_sheet_file_size')->nullable();

            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('INR');

            // pending | paid | failed | refunded
            $table->string('payment_status', 20)->default('pending');
            // pending_payment | awaiting_evaluation | under_evaluation | completed | cancelled
            $table->string('evaluation_status', 30)->default('pending_payment');

            $table->string('evaluated_file_path', 500)->nullable();
            $table->string('evaluated_file_original_name', 255)->nullable();

            // Existing SYS admin_users.id (no separate faculty system yet).
            $table->unsignedBigInteger('faculty_id')->nullable();

            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('evaluation_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'study_material_id'], 'idx_cts_student_material');
            $table->index(['payment_status', 'evaluation_status'], 'idx_cts_statuses');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->dropIfExists('ca_test_submissions');
    }
};
