<?php

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\User\User;

class Address extends Model
{
    protected $table = 'addresses';

    protected $fillable = [
        'user_id',
        'phone',
        'postal_address',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    // if there was no phone number added in address table returns the phon in User table instead
    protected function phone(): Attribute
    {
        return Attribute::make(
            get: fn($value) => $value ?: $this->user?->phone,
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
