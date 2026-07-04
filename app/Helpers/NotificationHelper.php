<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NotificationHelper
{
    public static function create(
        int $userId,
        string $title,
        string $message,
        bool $sendPush = true,
        ?string $actionUrl = null
    ): bool {
        try {
            DB::table('notifications')->insert([
                'user_id'    => $userId,
                'title'      => $title,
                'message'    => $message,
                'is_read'    => false,
                'created_at' => now(),
            ]);

            // Push mirror (additive, 2026-07-03): every notification-bell entry
            // for a student/firm also fires a browser/mobile push. The bell is
            // populated exclusively through this helper (notifications table),
            // so hooking here means EVERY bell action pushes — exactly once.
            // Queued + non-throwing, and safe inside DB transactions (database
            // queue: the job row commits with the surrounding transaction).
            // A no-op when the target has no device token or FCM is unconfigured.
            // Callers that ALREADY dispatch a richer, deep-linked explicit push
            // for the same user/event pass $sendPush = false to avoid duplicates.
            if ($sendPush) {
                \App\Jobs\SendUserPushJob::dispatch($userId, $title, $message, $actionUrl);
            }
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
