<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired to a single participant's user channel whenever a conversation they're
 * in changes (new message, or they read it). Drives the conversation-list
 * reorder/preview and the global unread badge without polling.
 *
 * $unreadCount is THIS user's unread count for the conversation (0 for the
 * sender / after they read); $totalUnread is their platform-wide unread total.
 */
class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $userId,
        public int $conversationId,
        public ?string $lastMessagePreview,
        public ?string $lastMessageAt,
        public ?string $lastMessageSenderType,
        public int $unreadCount,
        public int $totalUnread
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'ConversationUpdated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id'          => $this->conversationId,
            'last_message_preview'     => $this->lastMessagePreview,
            'last_message_at'          => $this->lastMessageAt,
            'last_message_sender_type' => $this->lastMessageSenderType,
            'unread_count'             => $this->unreadCount,
            'total_unread'             => $this->totalUnread,
        ];
    }
}
