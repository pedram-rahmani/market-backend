<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('نام و نام خانوادگی');
            $table->string('username')->unique()->comment('نام کاربری');
            $table->string('email')->unique()->comment('آدرس ایمیل');
            $table->string('phone', 20)->nullable()->comment('شماره تلفن');
            $table->string('avatar')->nullable()->comment('تصویر پروفایل');
            $table->timestamp('email_verified_at')->nullable()->comment('تاریخ تایید ایمیل');
            $table->string('password')->comment('رمز عبور');
            $table->string('role')->comment('سمت')->default('user');
            $table->string('status')->default('active')->comment('وضعیت کاربر');
            $table->text('admin_notes')->nullable()->comment("یادداشت ادمین");
            $table->timestamp('last_login_at')->nullable()->comment("آخرین بازدید");
            $table->json('permissions')->nullable()->comment('دسترسی ها');
            $table->softDeletes();

            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
