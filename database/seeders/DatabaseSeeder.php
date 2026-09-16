<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            ProductSeeder::class,
            ColorSeeder::class,
            ReviewSeeder::class,
            QuestionSeeder::class,
            ProductIntroductionSeeder::class,
            WalletSeeder::class,
            TicketSeeder::class,
            OrderSeeder::class,
            SettingSeeder::class,
            WarrantySeeder::class,
            CouponSeeder::class,
        ]);
    }
}
