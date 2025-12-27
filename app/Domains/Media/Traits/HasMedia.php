<?php

namespace App\Domains\Media\Traits;

use App\Domains\Media\Models\Media;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMedia
{
    /**
     * Get all media attached to this model.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->ordered();
    }

    /**
     * Get media for a specific collection.
     */
    public function getMedia(string $collection = 'default'): \Illuminate\Database\Eloquent\Collection
    {
        return $this->media()->collection($collection)->get();
    }

    /**
     * Get the first media item from a collection.
     */
    public function getFirstMedia(string $collection = 'default'): ?Media
    {
        return $this->media()->collection($collection)->first();
    }

    /**
     * Get the URL of the first media item from a collection.
     */
    public function getFirstMediaUrl(string $collection = 'default', string $conversion = null): ?string
    {
        $media = $this->getFirstMedia($collection);

        if (!$media) {
            return null;
        }

        if ($conversion) {
            return $media->getConversionUrl($conversion) ?? $media->url;
        }

        return $media->url;
    }

    /**
     * Check if the model has media in a collection.
     */
    public function hasMedia(string $collection = 'default'): bool
    {
        return $this->media()->collection($collection)->exists();
    }

    /**
     * Get the count of media items in a collection.
     */
    public function getMediaCount(string $collection = 'default'): int
    {
        return $this->media()->collection($collection)->count();
    }

    /**
     * Clear all media from a collection.
     */
    public function clearMediaCollection(string $collection = 'default'): void
    {
        $this->media()->collection($collection)->get()->each->delete();
    }

    /**
     * Get avatar URL (for providers).
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('avatar', 'medium');
    }

    /**
     * Get cover photo URL (for providers).
     */
    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('cover', 'large');
    }

    /**
     * Get gallery images.
     */
    public function getGalleryAttribute(): \Illuminate\Support\Collection
    {
        return $this->getMedia('gallery')->map->toMediaArray();
    }

    /**
     * Reorder media items.
     */
    public function reorderMedia(array $orderedIds, string $collection = 'default'): void
    {
        foreach ($orderedIds as $order => $id) {
            $this->media()
                ->collection($collection)
                ->where('id', $id)
                ->update(['order' => $order]);
        }
    }
}
