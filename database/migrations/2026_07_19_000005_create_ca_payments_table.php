<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ca_library database. One row per payment ATTEMPT — a submission can have
// several failed attempts before a paid one; attempts are never overwritten.
return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('ca_library')->create('ca_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('test_submission_id');

            $table->decimal('amount', 10, 2);
            $table->string('currency', 10)->default('INR');

            $table->string('gateway', 30)->default('phonepe');
            $table->string('gateway_order_id', 100)->nullable();
            $table->string('gateway_transaction_id', 100)->nullable();

            // pending | paid | failed
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index('test_submission_id', 'idx_cap_submission');
            $table->index('gateway_order_id', 'idx_cap_order');
        });
    }

    public function down(): void
    {
        Schema::connection('ca_library')->dropIfExists('ca_payments');
    }
};
