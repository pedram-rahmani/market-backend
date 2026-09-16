<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade')->comment('آیدی دسته‌بندی');
            $table->string('title')->comment('عنوان اصلی ویژگی به فارسی (مثلا: حافظه رم)');
            $table->string('name')->nullable()->comment('نام لاتین برای فیلتر در URL (مثلا: ram)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_features');
    }
};
