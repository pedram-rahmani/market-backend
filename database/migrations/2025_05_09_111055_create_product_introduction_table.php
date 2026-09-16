<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        Schema::create('product_introductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('title')->nullable()->comment('عنوان بخش');
            $table->text('content')->comment('محتوای اصلی بخش');
            $table->enum('type', ['paragraph', 'list', 'heading'])->comment('نوع محتوا: پاراگراف، لیست یا تیتر');
            $table->unsignedInteger('sort_order')->default(0)->comment('ترتیب نمایش');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_introduction');
    }
};
