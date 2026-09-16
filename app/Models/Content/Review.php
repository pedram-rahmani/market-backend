<?php

namespace App\Models\Content;

use App\Models\General\Report;
use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\User\User;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'parent_id',
        'rating',
        'comment',
        'is_approved'
    ];

    public function media()
    {
        return $this->hasMany(ReviewMedia::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function reactions()
    {
        return $this->hasMany(ReviewReaction::class);
    }

    public function reports(): MorphMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function parent()
    {
        return $this->belongsTo(Review::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(Review::class, 'parent_id');
    }
}
