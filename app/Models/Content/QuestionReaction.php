<?php

namespace App\Models\Content;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User\User;

class QuestionReaction extends Model
{
    use HasFactory;

    protected $table = 'question_reactions';

    protected $fillable = [
        'user_id',
        'question_id',
        'type',
    ];

    // Get the user who reacted
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Get the question being reacted to
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
