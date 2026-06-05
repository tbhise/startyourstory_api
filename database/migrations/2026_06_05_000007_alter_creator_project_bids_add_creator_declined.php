<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE creator_project_bids
            MODIFY COLUMN status
                ENUM('pending','shortlisted','selected','rejected','withdrawn','creator_declined')
                NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE creator_project_bids
            MODIFY COLUMN status
                ENUM('pending','shortlisted','selected','rejected','withdrawn')
                NOT NULL DEFAULT 'pending'
        ");
    }
};
