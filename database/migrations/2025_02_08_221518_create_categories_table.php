<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('name')->comment('عنوان فارسی دسته‌بندی برای نمایش در سایت');

            // استفاده از unique برای جلوگیری از تکراری بودن URLها
            $table->string('slug')->unique()->comment('نامک انگلیسی برای URL (یکتا)');

            // parent_id برای ایجاد ساختار درختی. index اضافه شده تا در کوئری‌های بزرگ سریع عمل کند
            $table->unsignedBigInteger('parent_id')->nullable()->index()->comment('آیدی دسته‌ی والد');

            $table->integer('level')->default(1)->comment('سطح در درخت: ۱ یعنی دسته اصلی، ۲ یعنی زیردسته');

            $table->string('path')->nullable()->comment('مسیر کامل برای breadcrumb');

            $table->timestamps();

            // تنظیم کلید خارجی با رعایت منطق cascade
            $table->foreign('parent_id')
                  ->references('id')
                  ->on('categories')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        // در زمان حذف جدول، ابتدا کلید خارجی را حذف می‌کنیم تا خطای ۱۷۰۱ رخ ندهد
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });

        Schema::dropIfExists('categories');
    }
};
