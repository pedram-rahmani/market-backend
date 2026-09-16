<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Model;

class ReviewMedia extends Model
{
    protected $fillable = ['review_id', 'file_path', 'file_type', 'disk', 'is_approved'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }
}
