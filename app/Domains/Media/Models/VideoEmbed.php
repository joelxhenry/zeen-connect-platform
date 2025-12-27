<?php

namespace App\Domains\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class VideoEmbed extends Model
{
    protected $fillable = [
        'uuid',
        'model_type',
        'model_id',
        'platform',
        'video_id',
        'url',
        'embed_code',
        'title',
        'thumbnail_url',
        'duration',
        'metadata',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'duration' => 'integer',
            'order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($video) {
            if (empty($video->uuid)) {
                $video->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the parent model.
     */
    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if this is a YouTube video.
     */
    public function isYouTube(): bool
    {
        return $this->platform === 'youtube';
    }

    /**
     * Check if this is a Vimeo video.
     */
    public function isVimeo(): bool
    {
        return $this->platform === 'vimeo';
    }

    /**
     * Get the embed URL for iframe.
     */
    public function getEmbedUrlAttribute(): string
    {
        return match ($this->platform) {
            'youtube' => "https://www.youtube.com/embed/{$this->video_id}",
            'vimeo' => "https://player.vimeo.com/video/{$this->video_id}",
            default => $this->url,
        };
    }

    /**
     * Get the watch URL for the video.
     */
    public function getWatchUrlAttribute(): string
    {
        return match ($this->platform) {
            'youtube' => "https://www.youtube.com/watch?v={$this->video_id}",
            'vimeo' => "https://vimeo.com/{$this->video_id}",
            default => $this->url,
        };
    }

    /**
     * Generate embed code if not set.
     */
    public function getEmbedCodeAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        return sprintf(
            '<iframe src="%s" width="560" height="315" frameborder="0" allowfullscreen></iframe>',
            $this->embed_url
        );
    }

    /**
     * Get human readable duration.
     */
    public function getHumanDurationAttribute(): ?string
    {
        if (!$this->duration) {
            return null;
        }

        $minutes = floor($this->duration / 60);
        $seconds = $this->duration % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    /**
     * Get the default thumbnail based on platform.
     */
    public function getDefaultThumbnailAttribute(): ?string
    {
        if ($this->thumbnail_url) {
            return $this->thumbnail_url;
        }

        return match ($this->platform) {
            'youtube' => "https://img.youtube.com/vi/{$this->video_id}/hqdefault.jpg",
            default => null,
        };
    }

    /**
     * Scope to filter by platform.
     */
    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to order by position.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Convert to array for API responses.
     */
    public function toVideoArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'platform' => $this->platform,
            'video_id' => $this->video_id,
            'url' => $this->url,
            'embed_url' => $this->embed_url,
            'embed_code' => $this->embed_code,
            'watch_url' => $this->watch_url,
            'title' => $this->title,
            'thumbnail_url' => $this->default_thumbnail,
            'duration' => $this->duration,
            'human_duration' => $this->human_duration,
            'order' => $this->order,
        ];
    }
}
