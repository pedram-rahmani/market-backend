<?php

namespace App\Events;

use App\Models\Chat\ChatConversation;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(public ChatConversation $conversation)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('staff-chats'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.conversation.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => $this->conversation->load('user:id,name,username'),
        ];
    }
}
