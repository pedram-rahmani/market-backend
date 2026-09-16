<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warranties', function (Blueprint $table) {
            $table->id();
            $table->string('title')->comment('عنوان گارانتی');
            $table->integer('duration_months')->nullable()->comment('مدت زمان گارانتی به ماه');
            $table->text('description')->nullable()->comment('توضیحات و شرایط گارانتی');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warranties');
    }
};
