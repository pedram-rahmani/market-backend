<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_code')->unique()->comment('کد پیگیری سفارش');
            $table->string('status')->default('current')->comment('وضعیت سفارش');
            $table->decimal('total_price', 12, 0)->comment('مبلغ کل سفارش');
            $table->decimal('discount', 12, 0)->default(0)->comment('مبلغ تخفیف');
            $table->string('tracking_code')->nullable()->comment('کد پیگیری مرجوعی');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
