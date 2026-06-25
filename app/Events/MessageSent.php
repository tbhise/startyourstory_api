<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a message is persisted. Delivered on the conversation channel so
 * the open thread appends it live (and the sender's other tabs stay in sync).
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $messageId,
        public int $senderId,
        public string $senderType,   // 'candidate' | 'firm'
        public string $message,
        public string $createdAt
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('conversation.' . $this->conversationId)];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->messageId,
            'conversation_id' => $this->conversationId,
            'sender_id'       => $this->senderId,
            'sender_type'     => $this->senderType,
            'message'         => $this->message,
            'created_at'      => $this->createdAt,
        ];
    }
}
