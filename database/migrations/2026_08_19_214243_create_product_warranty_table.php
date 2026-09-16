<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_warranty', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete()->comment('شناسه محصول');
            $table->foreignId('warranty_id')->constrained()->cascadeOnDelete()->comment('شناسه گارانتی');
            $table->unsignedBigInteger('price')->default(0)->comment('هزینه اضافی گارانتی (تومان/ریال)');
            $table->boolean('is_default')->default(false)->comment('آیا گارانتی پیش‌فرض محصول است؟');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_warranty');
    }
};
