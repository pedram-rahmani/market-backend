<?php

namespace App\Models\Product;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Content\Review;
use App\Models\Content\Question;
use App\Models\Product\Category;
use App\Models\Product\ProductColor;
use App\Models\Product\ProductIntroduction;
use App\Models\Product\ProductSpecification;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'slug',
        'sku',
        'description',
        'price',
        'stock',
        'sales_count',
        'discount',
        'discount_starts_at',
        'discount_expires_at',
        'rate',
        'category_id',
        'img',
        'options',
        'is_active',
        'featured',
    ];

    protected $casts = [
        'options' => 'array',
        'discount_starts_at' => 'datetime',
        'discount_expires_at' => 'datetime',
        'is_active' => 'boolean',
        'featured' => 'boolean',
        'price' => 'integer',
        'stock' => 'integer',
        'sales_count' => 'integer',
        'rate' => 'float',
    ];

    public function colors() { return $this->hasMany(ProductColor::class); }
    public function introductions() { return $this->hasMany(ProductIntroduction::class)->orderBy('sort_order', 'asc'); }
    public function specifications() { return $this->hasMany(ProductSpecification::class, 'product_id'); }
    public function reviews() { return $this->hasMany(Review::class); }
    public function questions() { return $this->hasMany(Question::class); }
    public function category() { return $this->belongsTo(Category::class); }
    public function warranties() { return $this->belongsToMany(\App\Models\Product\Warranty::class, 'product_warranty')->withPivot('price', 'is_default')->withTimestamps(); }
}
