<?php

namespace App\Domains\Media\Services;

use App\Domains\Media\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class MediaService
{
    public function __construct(
        protected ImageProcessingService $imageProcessor
    ) {}

    /**
     * Upload and attach media to a model.
     */
    public function upload(
        Model $model,
        UploadedFile $file,
        string $collection = 'default',
        ?string $disk = null,
        array $metadata = []
    ): Media {
        $disk = $disk ?? config('media.disk', 'public');
        $basePath = $this->getBasePath($model, $collection);

        // Validate file
        $this->validateUpload($file, $collection);

        // Check collection limit
        $this->checkCollectionLimit($model, $collection);

        // Process image and generate conversions
        $result = $this->imageProcessor->processImage($file, $basePath, $disk);

        // Get the next order
        $nextOrder = $model->media()
            ->where('collection', $collection)
            ->max('order') + 1;

        // Create media record
        return $model->media()->create([
            'collection' => $collection,
            'disk' => $disk,
            'path' => $result['original'],
            'filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'conversions' => $result['conversions'],
            'metadata' => array_merge($metadata, $this->imageProcessor->getImageDimensions($file)),
            'order' => $nextOrder,
        ]);
    }

    /**
     * Upload multiple files.
     *
     * @return array<Media>
     */
    public function uploadMultiple(
        Model $model,
        array $files,
        string $collection = 'default',
        ?string $disk = null
    ): array {
        $media = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $media[] = $this->upload($model, $file, $collection, $disk);
            }
        }

        return $media;
    }

    /**
     * Replace the single media item in a collection.
     */
    public function replaceMedia(
        Model $model,
        UploadedFile $file,
        string $collection
    ): Media {
        // Delete existing media in collection
        $model->clearMediaCollection($collection);

        // Upload new media
        return $this->upload($model, $file, $collection);
    }

    /**
     * Delete a media item.
     */
    public function delete(Media $media): bool
    {
        return $media->delete();
    }

    /**
     * Delete all media from a collection.
     */
    public function clearCollection(Model $model, string $collection): void
    {
        $model->clearMediaCollection($collection);
    }

    /**
     * Reorder media items.
     */
    public function reorder(Model $model, array $orderedIds, string $collection = 'default'): void
    {
        $model->reorderMedia($orderedIds, $collection);
    }

    /**
     * Move media to a different position.
     */
    public function moveMedia(Media $media, int $newOrder): void
    {
        DB::transaction(function () use ($media, $newOrder) {
            $currentOrder = $media->order;

            if ($newOrder === $currentOrder) {
                return;
            }

            // Shift other items
            if ($newOrder < $currentOrder) {
                // Moving up
                $media->model->media()
                    ->where('collection', $media->collection)
                    ->where('order', '>=', $newOrder)
                    ->where('order', '<', $currentOrder)
                    ->increment('order');
            } else {
                // Moving down
                $media->model->media()
                    ->where('collection', $media->collection)
                    ->where('order', '>', $currentOrder)
                    ->where('order', '<=', $newOrder)
                    ->decrement('order');
            }

            $media->update(['order' => $newOrder]);
        });
    }

    /**
     * Get the base storage path for a model.
     */
    protected function getBasePath(Model $model, string $collection): string
    {
        $modelType = class_basename($model);
        $modelId = $model->getKey();

        $basePaths = config('media.paths', []);
        $modelPath = $basePaths[strtolower($modelType . 's')] ?? "media/{$modelType}";

        return "{$modelPath}/{$modelId}/{$collection}";
    }

    /**
     * Validate an upload.
     *
     * @throws \InvalidArgumentException
     */
    protected function validateUpload(UploadedFile $file, string $collection): void
    {
        // Check if valid image
        if (!$this->imageProcessor->isValidImage($file)) {
            throw new \InvalidArgumentException(
                'Invalid file type. Allowed types: ' . implode(', ', config('media.allowed_mime_types'))
            );
        }

        // Check file size
        $maxSize = config('media.max_file_size', 10240) * 1024; // Convert KB to bytes
        if ($file->getSize() > $maxSize) {
            throw new \InvalidArgumentException(
                'File size exceeds the maximum allowed size of ' . (config('media.max_file_size', 10240) / 1024) . 'MB'
            );
        }
    }

    /**
     * Check if collection limit is reached.
     *
     * @throws \InvalidArgumentException
     */
    protected function checkCollectionLimit(Model $model, string $collection): void
    {
        $limits = config('media.collection_limits', []);
        $limit = $limits[$collection] ?? null;

        if ($limit === null) {
            return;
        }

        $currentCount = $model->getMediaCount($collection);

        if ($currentCount >= $limit) {
            throw new \InvalidArgumentException(
                "Maximum of {$limit} item(s) allowed in the '{$collection}' collection"
            );
        }
    }

    /**
     * Get remaining slots in a collection.
     */
    public function getRemainingSlots(Model $model, string $collection): ?int
    {
        $limits = config('media.collection_limits', []);
        $limit = $limits[$collection] ?? null;

        if ($limit === null) {
            return null; // Unlimited
        }

        $currentCount = $model->getMediaCount($collection);

        return max(0, $limit - $currentCount);
    }
}
