<?php

namespace App\Http\Controllers\Content;

use App\Http\Controllers\Controller;
use App\Models\Chat\ChatConversation;
use App\Models\Chat\ChatMessage;
use App\Events\ChatMessageSent;
use App\Events\ChatConversationUpdated;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Exception;

class ChatController extends Controller
{
    // Retrieve the current user's active conversation (or latest one) along with messages
    public function index(Request $request)
    {
        $user = $request->user();

        // dont open support chat for admins/staff
        if ($user->hasPermission('chats.view') || $user->hasPermission('chats.reply')) {
            return response()->json([
                'message' => 'ادمین‌ها نیاز به چت پشتیبانی شخصی ندارند.',
                'messages' => []
            ]);
        }

        // Find the user's latest open conversation
        $conversation = ChatConversation::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        // If no open conversation exists, create a new one
        if (!$conversation) {
            $conversation = ChatConversation::create([
                'user_id' => $user->id,
                'status' => 'open',
            ]);
        }

        $conversation->load([
            'messages' => fn ($query) => $query->with('user:id,name,username,role')
                ->latest()
                ->take(50)
                ->get()
                ->sortBy('created_at')
        ]);

        // Mark messages sent by staff as read
        ChatMessage::where('conversation_id', $conversation->id)
            ->where('user_id', '!=', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($conversation);
    }

    // Creates a new conversation if the previous one was closed
    public function store(Request $request, NotificationService $notifications)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = $request->user();

        // Check if the user has an open conversation
        $conversation = ChatConversation::where('user_id', $user->id)
            ->where('status', 'open')
            ->latest()
            ->first();

        // If no open conversation exists or the latest one is closed, create a brand new conversation
        if (!$conversation || $conversation->status === 'closed') {
            $conversation = ChatConversation::create([
                'user_id' => $user->id,
                'status' => 'open',
            ]);
        }

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Safely trigger broadcasting to prevent 500 errors if broadcasting/queue fails on hosting
        try {
            broadcast(new ChatMessageSent($message));
            broadcast(new ChatConversationUpdated($conversation->fresh()));
        } catch (Exception $e) {
            // broadcasting error
        }

        try {
            $notifications->notifyStaff(
                permission: 'chats.view',
                type: 'chat-management',
                title: 'پیام جدید در گفتگوی پشتیبانی',
                message: "پیام جدیدی از طرف {$user->name} دریافت شد.",
                targetLink: '/my-account/chat-management',
                exceptUserId: $user->id,
            );
        } catch (Exception $e) {
            // notification error
        }

        return response()->json([
            'message' => $message->load('user:id,name,username,role'),
            'conversation_id' => $conversation->id,
        ], 201);
    }

    // List all conversations for admin/staff with chats.view permission
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

    // Show a specific conversation for admin/staff with chats.view permission
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

    // Reply to a specific conversation by staff with chats.reply permission
    public function reply(Request $request, ChatConversation $conversation)
    {
        if (!$request->user()->hasPermission('chats.reply')) {
            abort(403, 'دسترسی غیرمجاز');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $user = $request->user();

        $message = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'message' => $validated['message'],
        ]);

        $conversation->update(['last_message_at' => now()]);

        try {
            broadcast(new ChatMessageSent($message));
            broadcast(new ChatConversationUpdated($conversation->fresh()));
        } catch (Exception $e) {
            // broadcasting error
        }

        return response()->json([
            'message' => $message->load('user:id,name,username,role'),
        ], 201);
    }

    // Update conversation status (open/closed) by staff with chats.reply permission
    public function updateStatus(Request $request, ChatConversation $conversation)
    {
        if (!$request->user()->hasPermission('chats.reply')) {
            abort(403, 'دسترسی غیرمجاز');
        }

        $validated = $request->validate([
            'status' => 'required|in:open,closed',
        ]);

        $conversation->update(['status' => $validated['status']]);

        try {
            broadcast(new ChatConversationUpdated($conversation->fresh()));
        } catch (Exception $e) {
            // broadcasting error
        }

        return response()->json([
            'message' => 'وضعیت گفتگو به‌روزرسانی شد.',
            'conversation' => $conversation->fresh(['user:id,name,username']),
        ]);
    }

    // Delete conversation by conversation owner or staff with chats.delete permission
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
