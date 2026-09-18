<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\General\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\User\User;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    private function authorizeManagement(): void
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->hasPermission('notifications.manage')), 403);
    }

    public function managementIndex()
    {
        $this->authorizeManagement();

        $history = Notification::query()
            ->where('type', 'admin-message')
            ->select('title', 'message', 'created_at', DB::raw('count(*) as recipient_count'))
            ->groupBy('title', 'message', 'created_at')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'recipients' => User::query()->select('id', 'name', 'username', 'email')->orderBy('name')->get(),
            'history' => $history,
        ]);
    }

    public function sendManagementMessage(Request $request)
    {
        $this->authorizeManagement();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'recipient_id' => 'nullable|integer|exists:users,id',
            'send_to_all' => 'sometimes|boolean',
        ]);

        if (empty($validated['recipient_id']) && empty($validated['send_to_all'])) {
            return response()->json(['message' => 'یک گیرنده انتخاب کنید یا ارسال همگانی را فعال کنید.'], 422);
        }

        $recipientIds = !empty($validated['send_to_all'])
            ? User::query()->pluck('id')
            : collect([$validated['recipient_id']]);

        $now = now();
        Notification::insert($recipientIds->map(fn (int $recipientId) => [
            'user_id' => $recipientId,
            'type' => 'admin-message',
            'title' => $validated['title'],
            'message' => $validated['message'],
            'is_read' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all());

        return response()->json(['message' => 'پیام با موفقیت ارسال شد.']);
    }

    // List all notifications for apiResource (with optional type filtering and pagination)
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $type = $request->input('type');

        $query = Notification::where('user_id', $userId);

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
        ]);
    }

    // Get unread notification counts grouped by type for the authenticated user
    public function getUnreadCounts(Request $request)
    {
        $userId = $request->user()->id;

        $rawCounts = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->select('type', DB::raw('count(*) as total'))
            ->groupBy('type')
            ->pluck('total', 'type')
            ->toArray();

        $counts = [
            'user-management' => $rawCounts['user-management'] ?? $rawCounts['user'] ?? 0,
            'product-management' => $rawCounts['product-management'] ?? $rawCounts['product'] ?? 0,
            'category-management' => $rawCounts['category-management'] ?? 0,
            'site-management' => $rawCounts['site-management'] ?? 0,
            'user-interactions' => $rawCounts['user-interactions'] ?? $rawCounts['comment'] ?? 0,
            'notifications' => ($rawCounts['notifications'] ?? 0) + ($rawCounts['admin-message'] ?? 0),
            'dashboard' => $rawCounts['dashboard'] ?? 0,
            'purchases' => $rawCounts['purchases'] ?? 0,
            'support' => $rawCounts['support'] ?? 0,
            'settings' => $rawCounts['settings'] ?? 0,
        ];

        return response()->json([
            'status' => 'success',
            'data' => $counts,
        ]);
    }

    // Get notifications list by type for a specific page (e.g. user interactions)
    public function getNotificationsByType(Request $request)
    {
        $userId = $request->user()->id;
        $type = $request->input('type');

        $query = Notification::where('user_id', $userId);

        if ($type) {
            $query->where('type', $type);
        }

        $notifications = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'status' => 'success',
            'data' => $notifications,
        ]);
    }

    // Mark notifications as read based on type or specific ID
    public function markAsRead(Request $request)
    {
        $userId = $request->user()->id;
        $type = $request->input('type');

        $query = Notification::where('user_id', $userId)->where('is_read', false);

        if ($type) {
            $query->where('type', $type);
        }

        $query->update(['is_read' => true]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notifications marked as read successfully.',
        ]);
    }

    // Show single notification for apiResource with ownership check
    public function show(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => $notification,
        ]);
    }

    // Delete single notification for apiResource (if needed)
    public function destroy(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized'
            ], 403);
        }

        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification deleted successfully.'
        ]);
    }
}
