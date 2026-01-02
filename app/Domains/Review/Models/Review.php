<?php

namespace App\Domains\Review\Models;

use App\Domains\Booking\Models\Booking;
use App\Domains\Media\Traits\HasMedia;
use App\Domains\Provider\Models\Provider;
use Database\Factories\ReviewFactory;
use App\Domains\Service\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Review extends Model
{
    use HasFactory, HasMedia;

    protected $fillable = [
        'uuid',
        'booking_id',
        'client_id',
        'provider_id',
        'service_id',
        'rating',
        'comment',
        'provider_response',
        'provider_responded_at',
        'is_visible',
        'is_flagged',
        'flag_reason',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_visible' => 'boolean',
        'is_flagged' => 'boolean',
        'provider_responded_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ReviewFactory
    {
        return ReviewFactory::new();
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Review $review) {
            if (empty($review->uuid)) {
                $review->uuid = (string) Str::uuid();
            }
        });

        // Update provider rating when review is created
        static::created(function (Review $review) {
            $review->provider->updateRatingStats();
        });

        // Update provider rating when review is updated
        static::updated(function (Review $review) {
            if ($review->wasChanged(['rating', 'is_visible'])) {
                $review->provider->updateRatingStats();
            }
        });

        // Update provider rating when review is deleted
        static::deleted(function (Review $review) {
            $review->provider->updateRatingStats();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true);
    }

    public function scopeForProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeWithRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    protected function ratingStars(): Attribute
    {
        return Attribute::get(fn () => str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating));
    }

    protected function formattedDate(): Attribute
    {
        return Attribute::get(fn () => $this->created_at->format('M j, Y'));
    }

    protected function timeAgo(): Attribute
    {
        return Attribute::get(fn () => $this->created_at->diffForHumans());
    }

    /*
    |--------------------------------------------------------------------------
    | Methods
    |--------------------------------------------------------------------------
    */

    public function hasProviderResponse(): bool
    {
        return ! empty($this->provider_response);
    }

    public function canBeRespondedTo(): bool
    {
        return ! $this->hasProviderResponse();
    }

    public function addProviderResponse(string $response): void
    {
        $this->update([
            'provider_response' => $response,
            'provider_responded_at' => now(),
        ]);
    }

    public function flag(string $reason): void
    {
        $this->update([
            'is_flagged' => true,
            'flag_reason' => $reason,
        ]);
    }

    public function hide(): void
    {
        $this->update(['is_visible' => false]);
    }

    public function show(): void
    {
        $this->update(['is_visible' => true]);
    }
}
