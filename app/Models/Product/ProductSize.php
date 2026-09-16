<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductSize extends Model
{
    use HasFactory;
    protected $fillable = ['product_id', 'name', 'description', 'type', 'min_measurement', 'max_measurement'];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
