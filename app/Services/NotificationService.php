<?php

namespace App\Services;

use App\Models\General\Notification;
use App\Models\User\User;

class NotificationService
{
    public function notifyUser(
        int $userId,
        string $type,
        string $title,
        string $message,
        ?string $targetLink = null,
    ): void {
        Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'target_link' => $targetLink,
            'is_read' => false,
        ]);
    }

    public function notifyAdmins(
        string $type,
        string $title,
        string $message,
        ?string $targetLink = null,
        ?int $exceptUserId = null,
    ): void {
        $adminIds = User::query()
            ->where('role', 'admin')
            ->when($exceptUserId, fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->pluck('id');

        if ($adminIds->isEmpty()) {
            return;
        }

        $now = now();
        $notifications = $adminIds->map(fn (int $adminId) => [
            'user_id' => $adminId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'target_link' => $targetLink,
            'is_read' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Notification::insert($notifications);
    }

    // اطلاع‌رسانی به ادمین‌ها و co-adminهایی که دسترسی مشخصی دارند (مثلا chats.view)
    public function notifyStaff(
        string $permission,
        string $type,
        string $title,
        string $message,
        ?string $targetLink = null,
        ?int $exceptUserId = null,
    ): void {
        $staff = User::query()
            ->whereIn('role', ['admin', 'co-admin'])
            ->when($exceptUserId, fn ($query) => $query->where('id', '!=', $exceptUserId))
            ->get(['id', 'role', 'permissions']);

        $recipientIds = $staff
            ->filter(fn (User $user) => $user->hasPermission($permission))
            ->pluck('id');

        if ($recipientIds->isEmpty()) {
            return;
        }

        $now = now();
        $notifications = $recipientIds->map(fn (int $userId) => [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'target_link' => $targetLink,
            'is_read' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ])->all();

        Notification::insert($notifications);
    }
}
