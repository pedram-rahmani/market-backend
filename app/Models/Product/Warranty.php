<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warranty extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'duration_months', 'description'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_warranty')
                    ->withPivot('price', 'is_default')
                    ->withTimestamps();
    }
}
