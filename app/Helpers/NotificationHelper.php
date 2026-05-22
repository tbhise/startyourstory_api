<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    public static function create(
        int $userId,
        string $title,
        string $message
    ): bool {
        try {
            DB::table('notifications')->insert([
                'user_id'    => $userId,
                'title'      => $title,
                'message'    => $message,
                'is_read'    => false,
                'created_at' => now(),
            ]);
            return true;
        } catch (\Exception $e) {
            Log::error(
                'Notification Create Error: ' .
                    $e->getMessage()
            );
            return false;
        }
    }
}
