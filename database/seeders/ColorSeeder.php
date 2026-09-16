<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product\Product;
use App\Models\Product\ProductColor;
use Illuminate\Support\Str; // این را اضافه کن

class ColorSeeder extends Seeder
{
    public function run(): void
    {
        $product = Product::first();

        if (!$product) {
            $product = Product::create([
                'name'        => 'Default Product',
                'slug'        => Str::slug('Default Product' . uniqid()), // تولید اسلاگ یکتا
                'description' => 'This is a default product for seeding colors',
                'price'       => 100,
                'discount'    => 0,
                'category_id' => 1, // مطمئن شو آیدی 1 در جدول categories وجود دارد
            ]);
        }

        $colors = [
            ['name' => 'قرمز', 'hex' => '#EF4444'],
            ['name' => 'آبی', 'hex' => '#3B82F6'],
            ['name' => 'سبز', 'hex' => '#10B981'],
            ['name' => 'زرد', 'hex' => '#FFFF00'],
            ['name' => 'سفید', 'hex' => '#FFFFFF'],
            ['name' => 'مشکی', 'hex' => '#000000'],
            ['name' => 'طلایی', 'hex' => '#F6D30F'],
            ['name' => 'نقره ای', 'hex' => '#C0C0C0'],
        ];

        foreach ($colors as $color) {
            // استفاده از updateOrCreate برای جلوگیری از تکراری شدن رنگ‌ها در هر بار سید
            ProductColor::updateOrCreate(
                ['product_id' => $product->id, 'name' => $color['name']],
                ['hex' => $color['hex']]
            );
        }
    }
}
