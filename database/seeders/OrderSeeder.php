<?php

namespace Database\Seeders;

use App\Models\Order\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Insert mock data matching the frontend tabs
        Order::create([
            'user_id' => 1, // Ensure a user with ID 1 exists
            'order_code' => '1175201749',
            'status' => 'delivered',
            'total_price' => 1335800,
            'discount' => 100000,
        ]);

        Order::create([
            'user_id' => 1,
            'order_code' => '3288371',
            'status' => 'returned',
            'total_price' => 420000,
            'discount' => 0,
            'tracking_code' => 'TRK-987654',
        ]);

        Order::create([
            'user_id' => 1,
            'order_code' => '345594425',
            'status' => 'cancelled',
            'total_price' => 69000,
            'discount' => 0,
        ]);

        Order::create([
            'user_id' => 1,
            'order_code' => '984512367',
            'status' => 'current',
            'total_price' => 850000,
            'discount' => 50000,
            'tracking_code' => 'TRK-112233',
        ]);
    }
}
