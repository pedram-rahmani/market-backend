<?php

namespace Database\Seeders;

use App\Models\General\Coupon;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        // ۱. کد تخفیف عمومی
        Coupon::create([
            'code' => 'WELCOME10',
            'type' => 'percent',
            'value' => 10.00,
            'min_order_price' => 100000.00,
            'max_discount' => 50000.00,
            'usage_limit' => 100,
            'expires_at' => now()->addDays(30),
            'is_active' => true,
        ]);

        // ۲. کد تخفیف اختصاصی برای یک کاربر خاص (مثلاً اولین کاربر سایت)
        $specialCoupon = Coupon::create([
            'code' => 'SPECIAL50',
            'type' => 'fixed',
            'value' => 50000.00,
            'min_order_price' => 200000.00,
            'usage_limit' => 1,
            'expires_at' => now()->addDays(7),
            'is_active' => true,
        ]);

        // اگر خواستی این کد فقط متعلق به کاربر خاصی باشد، ارتباطش را در جدول واسط ثبت کن:
        $user = User::first();
        if ($user) {
            // این یعنی این کد مختص این کاربر است (یا بهش هدیه داده شده)
            // اگر خواستی اجباری باشد که حتماً فقط خودش استفاده کند، می‌توانی توی کنترلر بررسی کنی
            // که آیا این کد در جدول coupon_user برای بقیه کاربران ثبت نشده باشد.
        }
    }
}
