<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;
use App\Models\Product\Product;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'parent_id',
        'body',
        'is_approved',
        'is_admin_answer',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_admin_answer' => 'boolean',
    ];

    // Get the user who created the question/reply
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get the associated product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Get the parent question if this is a reply
    public function parent()
    {
        return $this->belongsTo(Question::class, 'parent_id');
    }

    // Get all replies for this question
    public function replies()
    {
        return $this->hasMany(Question::class, 'parent_id');
    }

    // Get all reactions (likes/dislikes) for this question
    public function reactions()
    {
        return $this->hasMany(QuestionReaction::class);
    }
}
