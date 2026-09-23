<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->bigInteger('balance')->default(0)->comment('موجودی فعلی کیف پول به تومان');
            $table->bigInteger('amount')->nullable()->comment('مبلغ تراکنش (واریز/برداشت)');
            $table->enum('type', ['deposit', 'withdraw', 'purchase'])->nullable()->comment('نوع تراکنش');
            $table->enum('status_transaction', ['success', 'pending', 'failed'])->nullable()->comment('وضعیت آخرین تراکنش');
            $table->string('description')->nullable()->comment('توضیحات تراکنش');
            $table->string('ref_id')->nullable()->comment('کد پیگیری درگاه بانکی');
            $table->string('status')->default('active')->comment('وضعیت کیف پول: active, suspended, closed');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallets');
    }
};