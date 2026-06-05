<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE creator_engagements (
                id                      BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                creator_requirement_id  BIGINT UNSIGNED NOT NULL,
                bid_id                  BIGINT UNSIGNED NOT NULL,
                creator_id              BIGINT UNSIGNED NOT NULL,
                firm_id                 BIGINT UNSIGNED NOT NULL,
                accepted_bid_amount     DECIMAL(12,2)   NOT NULL,
                delivery_days           SMALLINT UNSIGNED NOT NULL DEFAULT 7,
                status                  ENUM(
                    'awaiting_payment','payment_pending','active',
                    'submitted','revision_requested','approved',
                    'payout_pending','completed','cancelled'
                ) NOT NULL DEFAULT 'awaiting_payment',
                creator_accepted_at     TIMESTAMP NULL,
                created_at              TIMESTAMP NULL,
                updated_at              TIMESTAMP NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_engagement_bid (bid_id),
                KEY idx_ce_creator_status (creator_id, status),
                KEY idx_ce_firm_status    (firm_id, status),
                KEY idx_ce_req            (creator_requirement_id),
                CONSTRAINT fk_ce_req FOREIGN KEY (creator_requirement_id)
                    REFERENCES creator_projects(id) ON DELETE CASCADE,
                CONSTRAINT fk_ce_bid FOREIGN KEY (bid_id)
                    REFERENCES creator_project_bids(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS creator_engagements');
    }
};
