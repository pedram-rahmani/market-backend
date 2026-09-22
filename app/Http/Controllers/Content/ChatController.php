<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Chat\ChatConversation;
use App\Models\Chat\ChatMessage;
use App\Events\ChatMessageSent;
use App\Events\ChatConversationUpdated;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    // دریافت گفتگوی کاربر جاری (در صورت نبود، ساخته می‌شود) به همراه پیام‌ها
    public function index(Request $request)
    {
        $user = $request->user();
        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'open']
        );

        $conversation->load(['messages' => fn ($query) => $query->with('user:id,name,username,role')->oldest()]);

        // پیام‌های ارسال‌شده توسط کارمندان را خوانده‌شده علامت‌گذاری می‌کنیم
        ChatMessage::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($conversation);
    }

    // ارسال پیام جدید توسط کاربر جاری در گفتگوی خودش
    public function store(Request $request, NotificationService $notifications)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $user = $request->user();
        $conversation = ChatConversation::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'open']
        );

        if ($conversation->status === 'closed') {
            $conversation->update(['status' => 'open']);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new ChatMessageSent($message));
        broadcast(new ChatConversationUpdated($conversation->fresh()));

        $notifications->notifyStaff(
            permission: 'chats.view',
            type: 'chat-management',
            title: 'پیام جدید در گفتگوی پشتیبانی',
            message: "پیام جدیدی از طرف {$user->name} دریافت شد.",
            targetLink: '/my-account/chat-management',
            exceptUserId: $user->id,
        );

        return response()->json([
            'message' => $message->load('user:id,name,username,role'),
        ], 201);
    }

    // لیست تمام گفتگوها برای ادمین/کارمند دارای دسترسی chats.view
    public function adminIndex(Request $request)
    {
        $conversations = ChatConversation::with(['user:id,name,username'])
            ->withCount(['messages as unread_count' => function ($query) use ($request) {
                $query->where('user_id', '!=', $request->user()->id)->where('is_read', false);
            }])
            ->with(['messages' => fn ($query) => $query->latest()->limit(1)])
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($conversations);
    }

    // نمایش یک گفتگوی مشخص برای ادمین/کارمند دارای دسترسی chats.view
    public function show(Request $request, ChatConversation $conversation)
    {
        if (!$request->user()->hasPermission('chats.view')) {
            abort(403, 'دسترسی غیرمجاز');
        }

        $conversation->load(['user:id,name,username', 'messages' => fn ($query) => $query->with('user:id,name,username,role')->oldest()]);

        ChatMessage::where('conversation_id', $conversation->id)
            ->where('user_id', $conversation->user_id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($conversation);
    }

    // ارسال پاسخ توسط کارمند دارای دسترسی chats.reply به یک گفتگوی مشخص
    public function reply(Request $request, ChatConversation $conversation)
    {
        if (!$request->user()->hasPermission('chats.reply')) {
            abort(403, 'دسترسی غیرمجاز');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
        ]);

        $user = $request->user();

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new ChatMessageSent($message));
        broadcast(new ChatConversationUpdated($conversation->fresh()));

        return response()->json([
            'message' => $message->load('user:id,name,username,role'),
        ], 201);
    }

    // تغییر وضعیت گفتگو (باز/بسته) توسط کارمند دارای دسترسی chats.reply
    public function updateStatus(Request $request, ChatConversation $conversation)
    {
        if (!$request->user()->hasPermission('chats.reply')) {
            abort(403, 'دسترسی غیرمجاز');
        }

        $validated = $request->validate([
            'status' => 'required|in:open,closed',
        ]);

        $conversation->update(['status' => $validated['status']]);

        broadcast(new ChatConversationUpdated($conversation->fresh()));

        return response()->json([
            'message' => 'وضعیت گفتگو به‌روزرسانی شد.',
            'conversation' => $conversation->fresh(['user:id,name,username']),
        ]);
    }

    // حذف گفتگو (توسط صاحب گفتگو یا کارمند دارای دسترسی chats.delete)
    public function destroy(Request $request, ChatConversation $conversation)
    {
        $user = $request->user();
        $isOwner = $conversation->user_id === $user->id;

        if (!$isOwner && !$user->hasPermission('chats.delete')) {
            abort(403, 'دسترسی غیرمجاز');
        }

        $conversation->delete();

        return response()->json([
            'message' => 'گفتگو با موفقیت حذف شد.',
        ]);
    }
}
