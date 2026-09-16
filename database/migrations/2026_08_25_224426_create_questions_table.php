<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('شناسه کاربری که پرسش یا پاسخ را ثبت کرده است');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('شناسه محصول مربوطه');

            $table->unsignedBigInteger('parent_id')->nullable()->comment('شناسه پرسش والد (در صورتی که این رکورد پاسخ به یک سوال باشد)');
            $table->foreign('parent_id')->references('id')->on('questions')->cascadeOnDelete();

            $table->text('body')->comment('متن پرسش یا پاسخ ثبت شده');
            $table->boolean('is_approved')->default(false)->comment('وضعیت تایید مدیریت (۰: در انتظار تایید، ۱: تایید شده)');
            $table->boolean('is_admin_answer')->default(false)->comment('مشخص‌کننده اینکه آیا این پاسخ رسمی از طرف ادمین/پشتیبانی است یا خیر');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
