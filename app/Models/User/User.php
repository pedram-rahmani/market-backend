<?php

namespace App\Models\User;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Casts\Attribute;

use App\Models\Order\Order;
use App\Models\Content\Ticket;
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

        return in_array($permission, $permissions);
    }

    public function hasRole($role): bool
    {
        return $this->role === $role;
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isCoAdmin(): bool
    {
        return $this->role === 'co-admin';
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }

    // availablePermissions
    public static array $availablePermissions = [
        // user-management
        'users.view'    => 'مشاهده لیست کاربران',
        'users.create'  => 'افزودن کاربر جدید',
        'users.edit'    => 'ویرایش اطلاعات کاربران',
        'users.delete'  => 'حذف یا آرشیو کاربران',
        'users.restore' => 'بازیابی کاربران حذف شده',
        'users.promote'  => 'ارتقا نقش کاربران',
        'users.demote'  => 'تنزل نقش کاربران',
        'users.forceDelete' => 'حذف دائمی کاربران',

        // product-management
        'products.view'   => 'مشاهده لیست محصولات',
        'products.create' => 'افزودن محصول جدید',
        'products.edit'   => 'ویرایش محصولات',
        'products.delete' => 'حذف محصولات',

        // مدیریت دسته بندی ها
        'categories.view'   => 'مشاهده دسته‌بندی‌ها',
        'categories.create' => 'ایجاد دسته‌بندی',
        'categories.edit'   => 'ویرایش دسته‌بندی',
        'categories.delete' => 'حذف دسته‌بندی',

        // content-management / contacts
        'comments.manage'  => 'تایید، ویرایش یا حذف دیدگاه‌ها',
        'questions.manage' => 'پاسخ به سوالات کاربران',
        'tickets.view'     => 'مشاهده تیکت‌های پشتیبانی',
        'tickets.reply'    => 'پاسخ به تیکت‌های پشتیبانی',

        // financial-management
        'orders.view'      => 'مشاهده سفارشات',
        'orders.edit'      => 'تغییر وضعیت سفارشات',
        'financial.reports' => 'مشاهده گزارشات مالی و درآمد',

        // site-management (settings)
        'settings.edit'    => 'تغییر تنظیمات سایت'
    ];
}
