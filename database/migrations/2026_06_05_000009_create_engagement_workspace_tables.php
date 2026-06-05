<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'resubmitted' to the engagement status lifecycle
        DB::statement("
            ALTER TABLE creator_engagements
            MODIFY COLUMN status ENUM(
                'awaiting_payment','payment_pending','active',
                'submitted','revision_requested','resubmitted','approved',
                'payout_pending','completed','cancelled'
            ) NOT NULL DEFAULT 'awaiting_payment'
        ");

        // One brief per engagement, written by the firm
        DB::statement("
            CREATE TABLE `engagement_briefs` (
              `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `engagement_id`    BIGINT UNSIGNED NOT NULL,
              `detailed_brief`   TEXT            NOT NULL,
              `additional_notes` TEXT            NULL,
              `updated_by`       BIGINT UNSIGNED NOT NULL,
              `created_at`       TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at`       TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `uq_eb_engagement` (`engagement_id`),
              CONSTRAINT `fk_eb_engagement` FOREIGN KEY (`engagement_id`)
                REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Files attached to the brief (firm uploads)
        DB::statement("
            CREATE TABLE `engagement_brief_attachments` (
              `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `engagement_id` BIGINT UNSIGNED NOT NULL,
              `file_path`     VARCHAR(500)    NOT NULL,
              `original_name` VARCHAR(255)    NOT NULL,
              `mime_type`     VARCHAR(100)    NULL,
              `file_size`     INT UNSIGNED    NULL,
              `uploaded_by`   BIGINT UNSIGNED NOT NULL,
              `created_at`    TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_eba_engagement` (`engagement_id`),
              CONSTRAINT `fk_eba_engagement` FOREIGN KEY (`engagement_id`)
                REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // One record per submission round; creator creates a new row each time
        DB::statement("
            CREATE TABLE `engagement_submissions` (
              `id`             BIGINT UNSIGNED  NOT NULL AUTO_INCREMENT,
              `engagement_id`  BIGINT UNSIGNED  NOT NULL,
              `creator_id`     BIGINT UNSIGNED  NOT NULL,
              `notes`          TEXT             NULL,
              `revision_round` TINYINT UNSIGNED NOT NULL DEFAULT 1,
              `status`         ENUM('submitted','revision_requested','approved') NOT NULL DEFAULT 'submitted',
              `revision_notes` TEXT             NULL,
              `reviewed_by`    BIGINT UNSIGNED  NULL,
              `reviewed_at`    TIMESTAMP        NULL,
              `created_at`     TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at`     TIMESTAMP        NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_es_engagement` (`engagement_id`),
              KEY `idx_es_creator`    (`creator_id`),
              CONSTRAINT `fk_es_engagement` FOREIGN KEY (`engagement_id`)
                REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Physical files and video links attached to each submission
        DB::statement("
            CREATE TABLE `engagement_submission_files` (
              `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `submission_id` BIGINT UNSIGNED NOT NULL,
              `engagement_id` BIGINT UNSIGNED NOT NULL,
              `file_path`     VARCHAR(500)    NULL,
              `original_name` VARCHAR(255)    NULL,
              `mime_type`     VARCHAR(100)    NULL,
              `file_size`     INT UNSIGNED    NULL,
              `video_link`    VARCHAR(1000)   NULL,
              `created_at`    TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_esf_submission` (`submission_id`),
              KEY `idx_esf_engagement` (`engagement_id`),
              CONSTRAINT `fk_esf_submission` FOREIGN KEY (`submission_id`)
                REFERENCES `engagement_submissions` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Append-only activity log for every workspace event
        DB::statement("
            CREATE TABLE `engagement_timeline` (
              `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `engagement_id` BIGINT UNSIGNED NOT NULL,
              `user_id`       BIGINT UNSIGNED NULL,
              `role`          ENUM('firm','creator','system') NOT NULL,
              `event`         VARCHAR(100)    NOT NULL,
              `note`          TEXT            NULL,
              `meta`          JSON            NULL,
              `created_at`    TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_et_engagement` (`engagement_id`, `created_at`),
              CONSTRAINT `fk_et_engagement` FOREIGN KEY (`engagement_id`)
                REFERENCES `creator_engagements` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS `engagement_timeline`');
        DB::statement('DROP TABLE IF EXISTS `engagement_submission_files`');
        DB::statement('DROP TABLE IF EXISTS `engagement_submissions`');
        DB::statement('DROP TABLE IF EXISTS `engagement_brief_attachments`');
        DB::statement('DROP TABLE IF EXISTS `engagement_briefs`');

        DB::statement("
            ALTER TABLE creator_engagements
            MODIFY COLUMN status ENUM(
                'awaiting_payment','payment_pending','active',
                'submitted','revision_requested','approved',
                'payout_pending','completed','cancelled'
            ) NOT NULL DEFAULT 'awaiting_payment'
        ");
    }
};
