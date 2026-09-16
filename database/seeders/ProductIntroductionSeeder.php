<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// ایمپورت از مسیرهای جدید:
use App\Models\Product\Product;
use App\Models\Product\ProductIntroduction;

class ProductIntroductionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product = Product::first();

        if (!$product) {
            return;
        }

        ProductIntroduction::insert([
            [
                'product_id' => $product->id,
                'type' => 'paragraph',
                'title' => null,
                'content' => 'ساعت‌های هوشمند برند اپل همیشه جزو لوکس‌ترین گجت‌ها محسوب می‌شدند.',
                'sort_order' => 1,
            ],
            [
                'product_id' => $product->id,
                'type' => 'heading',
                'title' => 'نکات مهم درباره این مدل',
                'content' => '',
                'sort_order' => 2,
            ],
            [
                'product_id' => $product->id,
                'type' => 'list',
                'title' => null,
                'content' => 'عملکرد دقیق قدم‌شمار و سنسورهای دیگر ممکن است خطا داشته باشد.',
                'sort_order' => 3,
            ],
            [
                'product_id' => $product->id,
                'type' => 'list',
                'title' => null,
                'content' => 'بیشتر سنسورهای سلامتی مانند اکسیژن و فشار خون غیرفعال هستند.',
                'sort_order' => 4,
            ],
            [
                'product_id' => $product->id,
                'type' => 'heading',
                'title' => 'جمع‌بندی',
                'content' => '',
                'sort_order' => 5,
            ],
            [
                'product_id' => $product->id,
                'type' => 'paragraph',
                'title' => null,
                'content' => 'اگر به دنبال یک ساعت هوشمند با طراحی مشابه اپل هستید...',
                'sort_order' => 6,
            ],
        ]);
    }
}
