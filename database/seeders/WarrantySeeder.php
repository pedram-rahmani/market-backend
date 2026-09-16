<?php

namespace Database\Seeders;

use App\Models\Product\Warranty;
use App\Models\Product\Product;
use Illuminate\Database\Seeder;

class WarrantySeeder extends Seeder
{
    public function run(): void
    {
        // ۱. ایجاد چند گارانتی نمونه
        $w1 = Warranty::create([
            'title' => 'گارانتی ۱۸ ماهه مدیاپردازش',
            'duration_months' => 18,
            'description' => 'شامل ۱۸ ماه خدمات پس از فروش و تعویض قطعات اصلی',
        ]);

        $w2 = Warranty::create([
            'title' => 'گارانتی اصالت و سلامت فیزیکی کالا',
            'duration_months' => null,
            'description' => 'تضمین ۷ روز مهلت بازگشت و تعویض در صورت مغایرت',
        ]);

        $w3 = Warranty::create([
            'title' => 'بیمه طلایی (سرقت و آب‌خوردگی)',
            'duration_months' => 12,
            'description' => 'پوشش کامل خسارات فیزیکی، سرقت و نوسانات برق به مدت یک سال',
        ]);

        // ۲. اتصال به اولین محصول برای تست (در صورت وجود)
        $product = Product::first();

        if ($product) {
            $product->warranties()->sync([
                $w1->id => ['price' => 0, 'is_default' => true],
                $w3->id => ['price' => 250000, 'is_default' => false], // گارانتی پولی
            ]);
        }
    }
}
