<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Wallet extends Model
{
    protected $fillable = ['user_id', 'balance', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // deposit to wallet
    public function deposit($amount, $description = null, $orderId = null)
    {
        return DB::transaction(function () use ($amount, $description, $orderId) {

            $wallet = Wallet::where('id', $this->id)->lockForUpdate()->first();

            $wallet->balance += $amount;
            $wallet->save();

            $this->balance = $wallet->balance;

            return $this->transactions()->create([
                'amount'       => $amount,
                'type'         => 'deposit',
                'description'  => $description,
                'order_id'     => $orderId,
                'reference_id' => 'DEP-' . strtoupper(Str::random(12))
            ]);
        });
    }

    // withdraw from wallet
    public function withdraw($amount, $description = null, $orderId = null)
    {
        return DB::transaction(function () use ($amount, $description, $orderId) {
            $wallet = Wallet::where('id', $this->id)->lockForUpdate()->first();

            if ($wallet->balance >= $amount) {
                $wallet->balance -= $amount;
                $wallet->save();

                $this->balance = $wallet->balance;

                $this->transactions()->create([
                    'amount'       => $amount,
                    'type'         => 'withdraw',
                    'description'  => $description,
                    'order_id'     => $orderId,
                    'reference_id' => 'WTH-' . strtoupper(Str::random(12))
                ]);

                return true;
            }

            return false;
        });
    }
}
