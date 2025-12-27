<?php

namespace App\Domains\Media\Traits;

use App\Domains\Media\Models\VideoEmbed;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasVideoEmbeds
{
    /**
     * Get all video embeds attached to this model.
     */
    public function videoEmbeds(): MorphMany
    {
        return $this->morphMany(VideoEmbed::class, 'model')->ordered();
    }

    /**
     * Get video embeds by platform.
     */
    public function getVideosByPlatform(string $platform): \Illuminate\Database\Eloquent\Collection
    {
        return $this->videoEmbeds()->platform($platform)->get();
    }

    /**
     * Get the first video embed.
     */
    public function getFirstVideo(): ?VideoEmbed
    {
        return $this->videoEmbeds()->first();
    }

    /**
     * Check if the model has video embeds.
     */
    public function hasVideos(): bool
    {
        return $this->videoEmbeds()->exists();
    }

    /**
     * Get the count of video embeds.
     */
    public function getVideoCount(): int
    {
        return $this->videoEmbeds()->count();
    }

    /**
     * Clear all video embeds.
     */
    public function clearVideoEmbeds(): void
    {
        $this->videoEmbeds()->delete();
    }

    /**
     * Get videos as array for API responses.
     */
    public function getVideosAttribute(): \Illuminate\Support\Collection
    {
        return $this->videoEmbeds->map->toVideoArray();
    }

    /**
     * Reorder video embeds.
     */
    public function reorderVideos(array $orderedIds): void
    {
        foreach ($orderedIds as $order => $id) {
            $this->videoEmbeds()
                ->where('id', $id)
                ->update(['order' => $order]);
        }
    }
}
