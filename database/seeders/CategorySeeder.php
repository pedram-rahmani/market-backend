<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('categories')->truncate();

        $categories = [
            ['name' => 'دیجیتال', 'children' => ['گوشی موبایل', 'لوازم جانبی موبایل', 'تبلت و کتاب‌خوان', 'دوربین و عکاسی']],
            ['name' => 'صوتی و تصویری', 'children' => ['تلویزیون', 'اسپیکر', 'پروژکتور', 'هدفون و هدست']],
            ['name' => 'خانه و آشپزخانه', 'children' => ['لوازم آشپزخانه', 'یخچال و فریزر', 'ماشین لباسشویی', 'ماشین ظرفشویی']],
            ['name' => 'مبلمان', 'children' => ['مبلمان نشیمن', 'مبلمان اداری', 'میز و صندلی']],
            ['name' => 'پوشاک و مد', 'children' => ['لباس مردانه', 'لباس زنانه', 'کیف و کفش', 'زیورآلات']],
            ['name' => 'زیبایی و سلامت', 'children' => []],
            ['name' => 'کالای پزشکی', 'children' => []],
            ['name' => 'لوازم تحریر', 'children' => []],
            ['name' => 'کتاب و مجله', 'children' => []],
            ['name' => 'تجهیزات اداری', 'children' => []],
            ['name' => 'لوازم سرگرمی', 'children' => []],
            ['name' => 'آلات موسیقی', 'children' => []],
            ['name' => 'ورزش و سفر', 'children' => []],
            ['name' => 'تجهیزات ایمنی', 'children' => []],
            ['name' => 'خودرو و موتورسیکلت', 'children' => []],
            ['name' => 'ابزار و ماشین آلات', 'children' => []],
            ['name' => 'الکترونیک و رباتیک', 'children' => []],
            ['name' => 'لوازم کشاورزی، باغبانی و گلخانه', 'children' => []],
            ['name' => 'حیوانات خانگی', 'children' => []],
            ['name' => 'تعمیرات و قطعات', 'children' => []],
        ];

        foreach ($categories as $category) {
            $parentId = DB::table('categories')->insertGetId([
                'name'      => $category['name'],
                'slug'      => Str::slug($category['name'], '-', 'fa'),
                'parent_id' => null,
                'level'     => 1,
                'path'      => 'products/' . Str::slug($category['name'], '-', 'fa'),
                'created_at'=> now(),
                'updated_at'=> now(),
            ]);

            foreach ($category['children'] as $childName) {
                DB::table('categories')->insert([
                    'name'      => $childName,
                    'slug'      => Str::slug($childName, '-', 'fa'),
                    'parent_id' => $parentId,
                    'level'     => 2,
                    'path'      => 'product-info/' . Str::slug($childName, '-', 'fa'),
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);
            }
        }

        Schema::enableForeignKeyConstraints();
    }
}
