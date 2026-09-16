<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('کد تخفیف یکتا');
            $table->enum('type', ['percent', 'fixed'])->comment('نوع تخفیف: درصدی یا مبلغ ثابت');
            $table->decimal('value', 12, 2)->comment('مقدار تخفیف (درصد یا مبلغ)');
            $table->decimal('min_order_price', 12, 2)->nullable()->comment('حداقل مبلغ سفارش برای استفاده از کد');
            $table->decimal('max_discount', 12, 2)->nullable()->comment('سقف مبلغ تخفیف (مخصوص تخفیف‌های درصدی)');
            $table->integer('usage_limit')->nullable()->comment('محدودیت تعداد کل دفعات استفاده');
            $table->integer('used_count')->default(0)->comment('تعداد دفعاتی که تاکنون استفاده شده است');
            $table->timestamp('expires_at')->nullable()->comment('تاریخ و زمان انقضای کد تخفیف');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال یا غیرفعال بودن کد');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
