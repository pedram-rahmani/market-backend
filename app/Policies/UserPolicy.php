<?php

namespace App\Policies;

use App\Models\User\User;
use Illuminate\Support\Facades\Log;

class UserPolicy
{

    public function viewAny(User $user)
    {
        $isAdmin = $user->isAdmin();
        $hasPermission = $user->hasPermission('users.view');

        Log::info('Checking viewAny permission', [
            'user_id' => $user->id,
            'role' => $user->role,
            'isAdmin' => $isAdmin,
            'hasPermission_users.view' => $hasPermission
        ]);

        return $isAdmin || $hasPermission;
    }

    public function create(User $user)
    {
        return $user->isAdmin() || $user->hasPermission('users.create');
    }

    public function update(User $currentUser, User $userToUpdate)
    {
        if ($currentUser->id === $userToUpdate->id) {
            return true;
        }

        if ($currentUser->isAdmin()) {
            return true;
        }

        return $currentUser->isCoAdmin()
            && !$userToUpdate->isAdmin()
            && $currentUser->hasPermission('users.edit');
    }

    public function viewDeleted(User $user)
    {
        return $user->isAdmin() || $user->hasPermission('users.view');
    }

    public function updatePermissions(User $user, User $targetUser): bool
    {
        return $user->isAdmin()
            || ($user->hasPermission('users.edit') && !$targetUser->isAdmin());
    }

    public function restore(User $user, User $targetUser)
    {
        return $user->isAdmin() || $user->hasPermission('users.restore');
    }

    public function delete(User $user, User $targetUser)
    {
        if ($user->id === $targetUser->id) return false;
        if ($targetUser->isAdmin()) return false;

        return $user->isAdmin() || $user->hasPermission('users.delete');
    }

    public function forceDelete(User $user, User $targetUser)
    {
        return $user->isAdmin() || $user->hasPermission('users.forceDelete');
    }

    public function promote(User $user)
    {
        return $user->isAdmin() || $user->hasPermission('users.promote');
    }

    public function demote(User $user)
    {
        return $user->isAdmin() || $user->hasPermission('users.promote');
    }
}
