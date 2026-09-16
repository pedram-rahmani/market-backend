<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->bigInteger('amount')->comment('مبلغ تراکنش به تومان');
            $table->string('type')->comment('نوع تراکنش: deposit, withdraw');
            $table->string('reference_id')->unique()->comment('شناسه مرجع یا کد پیگیری یکتا');
            $table->text('description')->nullable()->comment('توضیحات تراکنش');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
