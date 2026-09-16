<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\User\User;

class Report extends Model
{
    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'reason',
        'description',
    ];

    public function reportable(): MorphTo
    {
        return $this->morphs('reportable'); // اصلاح ساختار متد morphs
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
