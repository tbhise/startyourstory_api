<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Broadcast Channels (SYS messaging — Reverb)
|--------------------------------------------------------------------------
| $user is the app's users-table row, resolved by ApiAuthMiddleware via
| $request->setUserResolver(). Only firm <-> student messaging exists, so
| authorization is strictly "is this user a participant".
*/

// Per-user channel: global unread badge + conversation-list updates.
Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Per-conversation channel: live messages + read receipts. Only the two
// participants (the candidate, or the firm's owning user) may subscribe.
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    $conv = DB::table('conversations')->where('id', $conversationId)->first();
    if (!$conv) {
        return false;
    }

    if ($user->role === 'student') {
        return (int) $conv->candidate_id === (int) $user->id;
    }

    if ($user->role === 'firm') {
        $firm = DB::table('firm_profiles')->where('user_id', $user->id)->first();
        return $firm && (int) $conv->firm_id === (int) $firm->id;
    }

    return false;
});
