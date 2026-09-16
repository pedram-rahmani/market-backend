<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id()->comment('شناسه یکتا');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('آی‌دی کاربر دریافت‌کننده نوتیفیکیشن');
            $table->string('type')->comment('نوع نوتیفیکیشن برای تفکیک در سایدبار مثل user-interactions یا support');
            $table->string('title')->comment('عنوان نوتیفیکیشن');
            $table->text('message')->comment('متن یا پیام نوتیفیکیشن');
            $table->string('target_link')->nullable()->comment('لینک مقصد برای هدایت کاربر هنگام کلیک');
            $table->boolean('is_read')->default(false)->comment('وضعیت خوانده شدن نوتیفیکیشن');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
