<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class ProductSpecification extends Model
{
    protected $fillable = ['product_id', 'feature_id', 'value'];

    public function feature()
    {
        return $this->belongsTo(ProductFeature::class, 'feature_id');
    }
}
