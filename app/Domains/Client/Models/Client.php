<?php

namespace App\Domains\Client\Models;

use App\Models\User;
use App\Support\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    protected $fillable = [
        'user_id',
        'preferred_location',
        'preferences',
    ];

    protected function casts(): array
    {
        return [
            'preferences' => 'array',
        ];
    }

    /**
     * Get the user that owns this client profile.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Increment the total bookings count.
     */
    public function incrementBookings(): void
    {
        $this->increment('total_bookings');
    }
}
