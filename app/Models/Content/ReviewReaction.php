<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

class ReviewReaction extends Model
{
    protected $fillable = [
        'review_id',
        'user_id',
        'type'
    ];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
