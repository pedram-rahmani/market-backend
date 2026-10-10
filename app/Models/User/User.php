<?php

namespace App\Models\User;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

use App\Models\Order\Order;
use App\Models\Chat\ChatConversation;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'permissions',
        'status',
        'phone',
        'avatar',
        'admin_notes',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    //user's active phone number (default= number in addresses table if exists, otherwise the phone number in users table)
    protected function activePhone(): Attribute
    {
        return Attribute::make(
            get: function () {
                $defaultAddress = $this->addresses()->whereNotNull('phone')->first();
                return $defaultAddress?->phone ?? $this->phone;
            }
        );
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->isAdmin()) return true;

        $permissions = $this->permissions ?? [];

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?? [];
        }

        if (!is_array($permissions)) {
            return false;
        }

        if (array_key_exists($permission, $permissions)) {
            return (bool) $permissions[$permission];
        }

        if (in_array($permission, $permissions, true)) {
            return true;
        }

        // Keep permissions saved before the management permissions were consolidated.
        $legacyAliases = [
            'users.manage' => ['users.create', 'users.edit', 'users.delete', 'users.restore'],
            'products.manage' => ['products.create', 'products.edit', 'products.delete'],
            'categories.manage' => ['categories.create', 'categories.edit', 'categories.delete'],
            'chats.list' => ['chats.view'],
            'chats.read' => ['chats.view'],
        ];

        return collect($legacyAliases[$permission] ?? [])
            ->contains(fn (string $legacyPermission) => array_key_exists($legacyPermission, $permissions)
                ? (bool) $permissions[$legacyPermission]
                : in_array($legacyPermission, $permissions, true));
    }

    public function hasRole($role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return strtolower(trim((string) $this->role)) === 'admin';
    }

    public function isCoAdmin(): bool
    {
        return strtolower(trim((string) $this->role)) === 'co-admin';
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function chatConversation()
    {
        return $this->hasOne(ChatConversation::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

}
