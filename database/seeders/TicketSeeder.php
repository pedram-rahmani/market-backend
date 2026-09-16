<?php

namespace Database\Seeders;

// ایمپورت از مسیرهای جدید:
use App\Models\Content\Ticket;
use App\Models\User\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run()
    {
        $user = User::first();

        if ($user) {
            Ticket::create([
                'user_id' => $user->id,
                'subject' => 'مشکل در پرداخت',
                'message' => 'مبلغ از حسابم کسر شده ولی سفارش ثبت نشده.',
                'status' => 'active',
            ]);
        }
    }
}
