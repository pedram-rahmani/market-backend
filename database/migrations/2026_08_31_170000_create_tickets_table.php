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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade')->comment('شناسه کاربر ایجادکننده تیکت');
            $table->string('subject')->comment('موضوع تیکت');
            $table->string('department')->default('technical')->comment('دپارتمان مربوطه (فنی، مالی، فروش)');
            $table->string('priority')->default('medium')->comment('اولویت تیکت (کم، متوسط، زیاد)');
            $table->string('status')->default('open')->comment('وضعیت تیکت (باز، در انتظار پاسخ، بسته)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
