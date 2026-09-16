<?php

namespace App\Providers;

use App\Permissions\PermissionList;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
// اضافه کردن ایمپورت‌های مدل و پالیسی (برای ثبت صریح)
use App\Models\Product\Product;
use App\Policies\ProductPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Product::class => ProductPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // گیت‌های قبلی شما
        // Gate::define(PermissionList::PROMOTE_TO_COADMIN, function ($user) {
        //     return $user->role === 'admin';
        // });

        // Gate::define(PermissionList::DEMOTE_TO_USER, function ($user) {
        //     return $user->role === 'admin';
        // });

        // Gate::define(PermissionList::MANAGE_COMMENTS, function ($user) {
        //     return in_array($user->role, ['admin', 'coadmin']);
        // });

        // Gate::define(PermissionList::VOTE_ON_COMMENT, function ($user) {
        //     return $user->role === 'user';
        // });
    }
}
