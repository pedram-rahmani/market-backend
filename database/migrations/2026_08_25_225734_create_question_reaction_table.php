<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->comment('شناسه کاربری که واکنش داده است');
            $table->foreignId('question_id')->constrained()->cascadeOnDelete()->comment('شناسه پرسش مربوطه');
            $table->enum('type', ['like', 'dislike'])->comment('نوع واکنش: لایک یا دیسلایک');
            $table->timestamps();
            
            $table->unique(['user_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_reactions');
    }
};
