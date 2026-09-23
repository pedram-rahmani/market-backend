<?php

use App\Models\Chat\ChatConversation;
use Illuminate\Support\Facades\Broadcast;

// Chat view channel: only the conversation owner or a staff member with chats.view permission can join
Broadcast::channel('chat.{conversationId}', function ($user, int $conversationId) {
    $conversation = ChatConversation::find($conversationId);

    if (!$conversation) {
        return false;
    }

    if ($conversation->user_id === $user->id) {
        return ['id' => $user->id, 'name' => $user->name];
    }

    return $user->hasPermission('chats.view')
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});

// Staff chats channel: only admin/staff with chats.view permission can join
Broadcast::channel('staff-chats', function ($user) {
    return $user->hasPermission('chats.view')
        ? ['id' => $user->id, 'name' => $user->name]
        : false;
});
