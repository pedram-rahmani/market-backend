<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->string('file_path')->comment('مسیر ذخیره‌سازی');
            $table->enum('file_type', ['image', 'video'])->comment('نوع فایل');
            $table->string('disk')->default('local')->comment('دیسک ذخیره‌سازی');
            $table->boolean('is_approved')->default(false)->comment('وضعیت تایید فایل');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_media');
    }
};
