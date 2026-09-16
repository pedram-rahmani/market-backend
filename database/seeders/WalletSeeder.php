<?php

namespace Database\Seeders;

use App\Models\User\User;
use App\Models\User\Wallet;
use App\Models\User\Transaction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WalletSeeder extends Seeder
{
    public function run()
    {
        $users = User::all();

        foreach ($users as $user) {
            // ایجاد کیف پول فقط اگر قبلاً برای این کاربر ساخته نشده باشد (با firstOrCreate)
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'balance' => 500000,
                    'status'  => 'active'
                ]
            );

            // ایجاد تراکنش اولیه (هدیه خوش‌آمدگویی) فقط اگر قبلاً این تراکنش ثبت نشده باشد
            $exists = Transaction::where('wallet_id', $wallet->id)
                                ->where('description', 'هدیه خوش‌آمدگویی')
                                ->exists();

            if (!$exists) {
                Transaction::create([
                    'wallet_id'    => $wallet->id,
                    'amount'       => 500000,
                    'type'         => 'deposit',
                    'description'  => 'هدیه خوش‌آمدگویی',
                    'reference_id' => 'INIT-' . bin2hex(random_bytes(5))
                ]);
            }
        }
    }
}
