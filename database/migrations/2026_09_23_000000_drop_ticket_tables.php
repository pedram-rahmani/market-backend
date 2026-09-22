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
        // سیستم تیکت پشتیبانی با چت لایو جایگزین شده است
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('tickets');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('شناسه کاربر ایجادکننده تیکت');
            $table->string('subject')->comment('موضوع تیکت');
            $table->string('department')->default('technical')->comment('دپارتمان مربوطه (فنی، مالی، فروش)');
            $table->string('priority')->default('medium')->comment('اولویت تیکت (کم، متوسط، زیاد)');
            $table->string('status')->default('open')->comment('وضعیت تیکت (باز، در انتظار پاسخ، بسته)');
            $table->timestamps();
        });

        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ticket_id')->comment('شناسه تیکت مرتبط');
            $table->unsignedBigInteger('user_id')->comment('شناسه فرستنده پیام');
            $table->text('message')->comment('متن پیام رد و بدل شده');
            $table->timestamps();
            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
};
