<?php

namespace Database\Seeders;

use App\Models\General\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaultSettings = [
            'site_name'   => 'فروشگاه آنلاین شیک شاپ',
            'site_logo'   => '/images/logo/logo.png',
            'footer_text' => 'تمامی حقوق مادی و معنوی این سایت متعلق به شیک شاپ می‌باشد.',

            'social_links' => json_encode([
                ['name' => 'instagram', 'url' => 'https://instagram.com/shikshop'],
                ['name' => 'telegram', 'url' => 'https://t.me/shikshop'],
                ['name' => 'whatsapp', 'url' => 'https://wa.me/989123456789']
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

            'contact_info' => json_encode([
                ['type' => 'phone', 'value' => '02112345678'],
                ['type' => 'email', 'value' => 'info@shikshop.com'],
                ['type' => 'telegram', 'value' => 'shikshop_support@']
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

            'footer_links' => json_encode([
                [
                    'title' => 'راهنمای خرید',
                    'items' => [
                        ['label' => 'قوانین و مقررات', 'url' => '/terms'],
                        ['label' => 'رویه بازگرداندن کالا', 'url' => '/returns'],
                        ['label' => 'پرسش‌های متداول', 'url' => '/faq'],
                    ]
                ],
                [
                    'title' => 'شیک شاپ',
                    'items' => [
                        ['label' => 'درباره ما', 'url' => '/about-us'],
                        ['label' => 'تماس با ما', 'url' => '/contact-us'],
                        ['label' => 'پشتیبانی / تیکت', 'url' => '/tickets'],
                    ]
                ]
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),

            'trust_badges' => '<a href="https://trustseal.enamad.ir" target="_blank"><img src="/images/Enamad.png" alt="اینماد"></a>',
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }
    }
}
