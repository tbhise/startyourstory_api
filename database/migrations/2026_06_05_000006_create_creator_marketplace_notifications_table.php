<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE creator_marketplace_notifications (
                id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id     BIGINT UNSIGNED NOT NULL,
                type        VARCHAR(80)     NOT NULL,
                title       VARCHAR(255)    NOT NULL,
                body        TEXT            NULL,
                data        JSON            NULL,
                read_at     TIMESTAMP       NULL,
                created_at  TIMESTAMP       NULL,
                updated_at  TIMESTAMP       NULL,
                PRIMARY KEY (id),
                KEY idx_cmn_user_unread   (user_id, read_at),
                KEY idx_cmn_user_created  (user_id, created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS creator_marketplace_notifications');
    }
};
