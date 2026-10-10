<?php

namespace App\Policies;

use App\Models\Product\Category;
use App\Models\User\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('categories.view');
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('categories.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->isAdmin() || $user->hasPermission('categories.manage');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->isAdmin() || $user->hasPermission('categories.manage');
    }
}
