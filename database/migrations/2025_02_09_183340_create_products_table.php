<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('نام محصول');
            $table->string('brand')->nullable()->comment('نام برند محصول');
            $table->string('slug')->unique()->comment('اسلاگ (شناسه یکتای متنی)');
            $table->string('sku')->nullable()->unique()->comment('شناسه یکتای انبار (SKU)');
            $table->text('description')->nullable()->comment('توضیحات');
            $table->unsignedInteger('price')->nullable()->comment('قیمت (تومان)');
            $table->unsignedInteger('stock')->default(0)->comment('موجودی انبار');
            $table->unsignedInteger('sales_count')->default(0)->comment('تعداد فروش (برای سورت پرفروش‌ترین)');
            $table->unsignedTinyInteger('discount')->nullable()->comment('تخفیف (درصد)');
            $table->timestamp('discount_starts_at')->nullable()->comment('تاریخ و زمان شروع تخفیف');
            $table->timestamp('discount_expires_at')->nullable()->comment('تاریخ و زمان انقضای تخفیف');
            $table->decimal('rate', 2, 1)->nullable()->comment('امتیاز (مثلا 4.5)');
            $table->string('img')->nullable()->comment('مسیر تصویر');
            $table->json('options')->nullable()->comment('ویژگی‌های انتخابی محصول (سایز، اندازه صفحه و...)');
            $table->boolean('is_active')->default(true)->comment('وضعیت فعال/غیرفعال بودن نمایش در سایت');
            $table->boolean('featured')->default(false)->comment('پیشنهاد ویژه / شگفت‌انگیز');

            $table->foreignId('category_id')
                  ->constrained('categories')
                  ->onDelete('cascade')
                  ->comment('شناسه دسته‌بندی');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
