<?php

namespace App\Models\Order;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'user_id',
        'order_code',
        'status',
        'total_price',
        'discount',
        'tracking_code',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
