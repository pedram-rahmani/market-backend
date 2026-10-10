<?php

use App\Models\Chat\ChatConversation;
use Illuminate\Support\Facades\Broadcast;

// Chat channel: owners and staff who can read or reply may join.
Broadcast::channel('chat.{conversationId}', function ($user, int $conversationId) {
    $conversation = ChatConversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    if ($conversation->user_id === $user->id) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    return $user->hasPermission('chats.read') || $user->hasPermission('chats.reply')
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});

// Staff list channel: only staff who can list conversations may join.
Broadcast::channel('staff-chats', function ($user) {
    return $user->hasPermission('chats.list')
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});
