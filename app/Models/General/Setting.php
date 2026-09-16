<?php

namespace App\Models\General;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * متد تبدیل خودکار فیلدها در هنگام خروجی گرفتن
     */
    protected function casts(): array
    {
        return [
            // if you dicided to use direct casting ...
        ];
    }
}
