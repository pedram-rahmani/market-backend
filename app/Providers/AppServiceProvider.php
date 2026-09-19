<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // reset password
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });

        // symlink storage for liara
        $publicStorage = public_path('storage');
        $storageAppPublic = storage_path('app/public');

        if (!File::exists($publicStorage)) {
            if (File::exists($storageAppPublic)) {
                File::link($storageAppPublic, $publicStorage);
            }
        }
    }
}
