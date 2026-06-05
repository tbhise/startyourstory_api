<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Creator bank account details — one record per creator, updated in place
        DB::statement("
            CREATE TABLE `creator_bank_details` (
              `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `creator_id`           BIGINT UNSIGNED NOT NULL,
              `account_holder_name`  VARCHAR(255)    NOT NULL,
              `bank_name`            VARCHAR(255)    NOT NULL,
              `account_number`       TEXT            NOT NULL,
              `ifsc_code`            VARCHAR(500)    NOT NULL,
              `is_verified`          TINYINT(1)      NOT NULL DEFAULT 0,
              `created_at`           TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at`           TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_cbd_creator` (`creator_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // One payout record per engagement, created when engagement hits payout_pending.
        // Stores gross amount, commission snapshot, and net amount for the creator.
        DB::statement("
            CREATE TABLE `creator_payouts` (
              `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `engagement_id`         BIGINT UNSIGNED NOT NULL,
              `creator_id`            BIGINT UNSIGNED NOT NULL,
              `gross_amount`          DECIMAL(12,2)   NOT NULL,
              `commission_rate`       DECIMAL(5,2)    NOT NULL DEFAULT 10.00,
              `commission_amount`     DECIMAL(12,2)   NOT NULL,
              `net_amount`            DECIMAL(12,2)   NOT NULL,
              `status`                ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
              `transaction_reference` VARCHAR(500)    NULL,
              `paid_at`               TIMESTAMP       NULL,
              `admin_notes`           TEXT            NULL,
              `processed_by`          BIGINT UNSIGNED NULL,
              `created_at`            TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at`            TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_cp_engagement` (`engagement_id`),
              KEY `idx_cp_creator_status` (`creator_id`, `status`),
              KEY `idx_cp_status`         (`status`),
              CONSTRAINT `fk_cp_engagement` FOREIGN KEY (`engagement_id`)
                REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Platform-wide configurable settings key-value store
        DB::statement("
            CREATE TABLE `platform_settings` (
              `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `key`         VARCHAR(100)    NOT NULL,
              `value`       TEXT            NOT NULL,
              `description` VARCHAR(500)    NULL,
              `updated_by`  BIGINT UNSIGNED NULL,
              `updated_at`  TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_ps_key` (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        DB::table('platform_settings')->insert([
            'key'         => 'commission_percentage',
            'value'       => '10',
            'description' => 'Platform commission percentage deducted from creator payouts',
        ]);
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS `creator_payouts`');
        DB::statement('DROP TABLE IF EXISTS `creator_bank_details`');
        DB::statement('DROP TABLE IF EXISTS `platform_settings`');
    }
};
