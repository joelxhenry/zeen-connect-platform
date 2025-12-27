<?php

namespace App\Domains\Media\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'uuid',
        'model_type',
        'model_id',
        'collection',
        'disk',
        'path',
        'filename',
        'mime_type',
        'size',
        'conversions',
        'metadata',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'conversions' => 'array',
            'metadata' => 'array',
            'size' => 'integer',
            'order' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($media) {
            if (empty($media->uuid)) {
                $media->uuid = (string) Str::uuid();
            }
        });

        static::deleting(function ($media) {
            $media->deleteFiles();
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
     * Get the full URL of the original file.
     */
    public function getUrlAttribute(): string
    {
        try {
            return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes(30));
        } catch (\Exception $e) {
            // Fallback to regular URL
        }
        return Storage::disk($this->disk)->url($this->path);
    }

    /**
     * Get the URL for a specific conversion.
     */
    public function getConversionUrl(string $conversion): ?string
    {
        $__conversion = Arr::get($this->conversions, $conversion, null);

        if (!$__conversion) {
            return null;
        }

        // Get temporary URL if disk supports it
        try {
            return Storage::disk($this->disk)->temporaryUrl($__conversion, now()->addMinutes(30));
        } catch (\Exception $e) {
            // Fallback to regular URL
        }

        return Storage::disk($this->disk)->url($__conversion);
    }

    /**
     * Get thumbnail URL (150px).
     */
    public function getThumbnailAttribute(): ?string
    {
        return $this->getConversionUrl('thumbnail') ?? $this->url;
    }

    /**
     * Get medium URL (600px).
     */
    public function getMediumAttribute(): ?string
    {
        return $this->getConversionUrl('medium') ?? $this->url;
    }

    /**
     * Get large URL (1200px).
     */
    public function getLargeAttribute(): ?string
    {
        return $this->getConversionUrl('large') ?? $this->url;
    }

    /**
     * Check if the media is an image.
     */
    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get human readable file size.
     */
    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Delete the file and all conversions from storage.
     */
    public function deleteFiles(): void
    {
        $disk = Storage::disk($this->disk);

        // Delete original
        if ($disk->exists($this->path)) {
            $disk->delete($this->path);
        }

        // Delete conversions
        if ($this->conversions) {
            foreach ($this->conversions as $path) {
                if ($disk->exists($path)) {
                    $disk->delete($path);
                }
            }
        }
    }

    /**
     * Set a conversion path.
     */
    public function setConversion(string $name, string $path): void
    {
        $conversions = $this->conversions ?? [];
        $conversions[$name] = $path;
        $this->conversions = $conversions;
    }

    /**
     * Get the file extension.
     */
    public function getExtensionAttribute(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    /**
     * Scope to filter by collection.
     */
    public function scopeCollection($query, string $collection)
    {
        return $query->where('collection', $collection);
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
    public function toMediaArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'collection' => $this->collection,
            'filename' => $this->filename,
            'mime_type' => $this->mime_type,
            'size' => $this->size,
            'human_size' => $this->human_size,
            'url' => $this->url,
            'thumbnail' => $this->thumbnail,
            'medium' => $this->medium,
            'large' => $this->large,
            'is_image' => $this->isImage(),
            'order' => $this->order,
        ];
    }
}
