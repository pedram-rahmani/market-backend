<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupon_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained('coupons')->cascadeOnDelete()->comment('شناسه کد تخفیف');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('شناسه کاربر');
            $table->timestamp('used_at')->useCurrent()->comment('تاریخ و زمان استفاده از کد');
            $table->unique(['coupon_id', 'user_id'], 'coupon_user_unique')->comment('جلوگیری از استفاده تکراری یک کد توسط یک کاربر');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupon_user');
    }
};
