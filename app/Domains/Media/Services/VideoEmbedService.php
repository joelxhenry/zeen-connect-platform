<?php

namespace App\Domains\Media\Services;

use App\Domains\Media\Models\VideoEmbed;
use Illuminate\Database\Eloquent\Model;

class VideoEmbedService
{
    /**
     * Add a video embed to a model from URL.
     */
    public function addFromUrl(Model $model, string $url, ?string $title = null): VideoEmbed
    {
        $parsed = $this->parseVideoUrl($url);

        if (!$parsed) {
            throw new \InvalidArgumentException('Invalid video URL. Supported platforms: YouTube, Vimeo');
        }

        // Get the next order
        $nextOrder = $model->videoEmbeds()->max('order') + 1;

        return $model->videoEmbeds()->create([
            'platform' => $parsed['platform'],
            'video_id' => $parsed['video_id'],
            'url' => $url,
            'title' => $title,
            'thumbnail_url' => $this->getThumbnailUrl($parsed['platform'], $parsed['video_id']),
            'order' => $nextOrder,
        ]);
    }

    /**
     * Add a video embed from embed code.
     */
    public function addFromEmbedCode(Model $model, string $embedCode, ?string $title = null): VideoEmbed
    {
        $parsed = $this->parseEmbedCode($embedCode);

        if (!$parsed) {
            throw new \InvalidArgumentException('Invalid embed code. Supported platforms: YouTube, Vimeo');
        }

        // Get the next order
        $nextOrder = $model->videoEmbeds()->max('order') + 1;

        return $model->videoEmbeds()->create([
            'platform' => $parsed['platform'],
            'video_id' => $parsed['video_id'],
            'url' => $this->buildWatchUrl($parsed['platform'], $parsed['video_id']),
            'embed_code' => $embedCode,
            'title' => $title,
            'thumbnail_url' => $this->getThumbnailUrl($parsed['platform'], $parsed['video_id']),
            'order' => $nextOrder,
        ]);
    }

    /**
     * Parse a video URL to extract platform and video ID.
     *
     * @return array{platform: string, video_id: string}|null
     */
    public function parseVideoUrl(string $url): ?array
    {
        $platforms = config('media.video_platforms', []);

        foreach ($platforms as $platform => $config) {
            foreach ($config['patterns'] as $pattern) {
                if (preg_match($pattern, $url, $matches)) {
                    return [
                        'platform' => $platform,
                        'video_id' => $matches[1],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Parse an embed code to extract platform and video ID.
     *
     * @return array{platform: string, video_id: string}|null
     */
    public function parseEmbedCode(string $embedCode): ?array
    {
        // Extract src from iframe
        if (preg_match('/src=["\']([^"\']+)["\']/i', $embedCode, $matches)) {
            return $this->parseVideoUrl($matches[1]);
        }

        return null;
    }

    /**
     * Update a video embed.
     */
    public function update(VideoEmbed $video, array $data): VideoEmbed
    {
        $video->update($data);
        return $video->fresh();
    }

    /**
     * Delete a video embed.
     */
    public function delete(VideoEmbed $video): bool
    {
        return $video->delete();
    }

    /**
     * Reorder video embeds.
     */
    public function reorder(Model $model, array $orderedIds): void
    {
        $model->reorderVideos($orderedIds);
    }

    /**
     * Get thumbnail URL for a video.
     */
    protected function getThumbnailUrl(string $platform, string $videoId): ?string
    {
        return match ($platform) {
            'youtube' => "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg",
            'vimeo' => null, // Vimeo requires API call for thumbnail
            default => null,
        };
    }

    /**
     * Build the watch URL for a video.
     */
    protected function buildWatchUrl(string $platform, string $videoId): string
    {
        return match ($platform) {
            'youtube' => "https://www.youtube.com/watch?v={$videoId}",
            'vimeo' => "https://vimeo.com/{$videoId}",
            default => '',
        };
    }

    /**
     * Validate a video URL.
     */
    public function isValidVideoUrl(string $url): bool
    {
        return $this->parseVideoUrl($url) !== null;
    }

    /**
     * Validate embed code.
     */
    public function isValidEmbedCode(string $embedCode): bool
    {
        return $this->parseEmbedCode($embedCode) !== null;
    }

    /**
     * Get the embed URL for a video.
     */
    public function getEmbedUrl(string $platform, string $videoId): string
    {
        return match ($platform) {
            'youtube' => "https://www.youtube.com/embed/{$videoId}",
            'vimeo' => "https://player.vimeo.com/video/{$videoId}",
            default => '',
        };
    }

    /**
     * Generate embed code from URL.
     */
    public function generateEmbedCode(string $url, int $width = 560, int $height = 315): ?string
    {
        $parsed = $this->parseVideoUrl($url);

        if (!$parsed) {
            return null;
        }

        $embedUrl = $this->getEmbedUrl($parsed['platform'], $parsed['video_id']);

        return sprintf(
            '<iframe width="%d" height="%d" src="%s" frameborder="0" allowfullscreen></iframe>',
            $width,
            $height,
            $embedUrl
        );
    }
}
