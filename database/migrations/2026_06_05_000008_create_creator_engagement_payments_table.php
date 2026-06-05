<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE `creator_engagement_payments` (
              `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `engagement_id`       BIGINT UNSIGNED NOT NULL,
              `firm_id`             BIGINT UNSIGNED NOT NULL,
              `amount`              DECIMAL(12,2)   NOT NULL,
              `currency`            VARCHAR(10)     NOT NULL DEFAULT 'INR',
              `payment_method`      ENUM('razorpay','manual') NOT NULL,
              `status`              ENUM('pending','paid','awaiting_verification','verified','escrow_held','refunded')
                                    NOT NULL DEFAULT 'pending',

              -- Online gateway fields (Razorpay / future providers)
              `gateway_order_id`    VARCHAR(255)    NULL,
              `gateway_payment_id`  VARCHAR(255)    NULL,
              `gateway_signature`   TEXT            NULL,
              `gateway_response`    JSON            NULL,

              -- Manual payment proof fields
              `utr_number`          VARCHAR(100)    NULL,
              `payment_reference`   VARCHAR(255)    NULL,
              `screenshot_url`      TEXT            NULL,
              `payment_date`        DATE            NULL,

              -- Admin review fields
              `admin_remarks`       TEXT            NULL,
              `reviewed_by`         BIGINT UNSIGNED NULL,
              `reviewed_at`         TIMESTAMP       NULL,

              `created_at`          TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at`          TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_cep_engagement`  (`engagement_id`),
              KEY `idx_cep_firm_status`        (`firm_id`, `status`),
              KEY `idx_cep_gateway_order`      (`gateway_order_id`),
              KEY `idx_cep_status`             (`status`),

              CONSTRAINT `fk_cep_engagement` FOREIGN KEY (`engagement_id`)
                REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS `creator_engagement_payments`');
    }
};
