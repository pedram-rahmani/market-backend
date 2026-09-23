<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $fillable = [
        'user_id', 
        'balance', 
        'status', 
        'amount', 
        'type', 
        'status_transaction', 
        'description', 
        'ref_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}