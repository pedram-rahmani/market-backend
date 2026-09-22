<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('شناسه کاربر صاحب گفتگو');
            $table->string('status')->default('open')->comment('وضعیت گفتگو (باز، بسته)');
            $table->timestamp('last_message_at')->nullable()->comment('زمان آخرین پیام برای مرتب‌سازی لیست گفتگوها');
            $table->timestamps();
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade')->comment('شناسه گفتگوی مرتبط');
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('شناسه فرستنده پیام');
            $table->text('message')->comment('متن پیام رد و بدل شده');
            $table->boolean('is_read')->default(false)->comment('وضعیت خوانده شدن پیام توسط طرف مقابل');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
