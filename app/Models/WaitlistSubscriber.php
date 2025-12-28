<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaitlistSubscriber extends Model
{
    protected $fillable = [
        'email',
        'name',
        'phone',
        'source',
        'is_founding_member',
        'subscribed_at',
    ];

    protected $casts = [
        'is_founding_member' => 'boolean',
        'subscribed_at' => 'datetime',
    ];
}
