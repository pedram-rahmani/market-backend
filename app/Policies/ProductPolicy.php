<?php

namespace App\Policies;

use App\Models\Product\Product;
use App\Models\User\User;
use Illuminate\Support\Facades\Log;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->hasPermission('products.view');
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->hasPermission('products.view');
    }

    public function create(User $user): bool
    {
        Log::info('User permissions check:', [
            'user_id' => $user->id,
            'user_permissions' => $user->permissions
        ]);

        return $user->isAdmin() || $user->hasPermission('products.create');
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->hasPermission('products.edit');
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->hasPermission('products.delete');
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->hasPermission('products.restore');
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->isAdmin() || $user->hasPermission('products.forceDelete');
    }
}
