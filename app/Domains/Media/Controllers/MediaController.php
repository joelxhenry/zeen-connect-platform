<?php

namespace App\Domains\Media\Controllers;

use App\Domains\Media\Models\Media;
use App\Domains\Media\Requests\ReorderMediaRequest;
use App\Domains\Media\Requests\UploadMediaRequest;
use App\Domains\Media\Requests\UploadMultipleMediaRequest;
use App\Domains\Media\Services\MediaService;
use App\Domains\Provider\Models\Provider;
use App\Domains\Review\Models\Review;
use App\Domains\Service\Models\Service;
use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MediaController extends Controller
{
    public function __construct(
        private MediaService $mediaService
    ) {}

    /**
     * Upload media to a provider.
     */
    public function uploadProviderMedia(UploadMediaRequest $request): JsonResponse
    {
        $provider = Auth::user()->provider;

        if (!$provider) {
            return ApiResponse::notFound('Provider not found');
        }

        try {
            $media = $this->mediaService->upload(
                $provider,
                $request->file('file'),
                $request->input('collection')
            );

            return ApiResponse::success(['media' => $media->toMediaArray()]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Upload multiple media items to a provider (for gallery).
     */
    public function uploadProviderMediaMultiple(UploadMultipleMediaRequest $request): JsonResponse
    {
        $provider = Auth::user()->provider;

        if (!$provider) {
            return ApiResponse::notFound('Provider not found');
        }

        try {
            $media = $this->mediaService->uploadMultiple(
                $provider,
                $request->file('files'),
                $request->input('collection', 'gallery')
            );

            return ApiResponse::success(['media' => array_map(fn ($m) => $m->toMediaArray(), $media)]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Upload multiple media items to a service.
     */
    public function uploadServiceMedia(UploadMultipleMediaRequest $request, Service $service): JsonResponse
    {
        $provider = Auth::user()->provider;

        if (!$provider || $service->provider_id !== $provider->id) {
            return ApiResponse::forbidden();
        }

        try {
            $media = $this->mediaService->uploadMultiple(
                $service,
                $request->file('files'),
                $request->input('collection', 'gallery')
            );

            return ApiResponse::success(['media' => array_map(fn ($m) => $m->toMediaArray(), $media)]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Upload single media item to a service.
     */
    public function uploadSingleServiceMedia(UploadMediaRequest $request, Service $service): JsonResponse
    {
        $provider = Auth::user()->provider;

        if (!$provider || $service->provider_id !== $provider->id) {
            return ApiResponse::forbidden();
        }

        try {
            $media = $this->mediaService->upload(
                $service,
                $request->file('file'),
                $request->input('collection', 'gallery')
            );

            return ApiResponse::success(['media' => $media->toMediaArray()]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Upload media to a review.
     */
    public function uploadReviewMedia(UploadMultipleMediaRequest $request, Review $review): JsonResponse
    {
        $user = Auth::user();

        if ($review->user_id !== $user->id) {
            return ApiResponse::forbidden();
        }

        try {
            $media = $this->mediaService->uploadMultiple(
                $review,
                $request->file('files'),
                'review_images'
            );

            return ApiResponse::success(['media' => array_map(fn ($m) => $m->toMediaArray(), $media)]);
        } catch (\InvalidArgumentException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }
    }

    /**
     * Delete a media item.
     */
    public function destroy(Media $media): JsonResponse
    {
        $user = Auth::user();

        // Check authorization based on model type
        if (!$this->canDeleteMedia($user, $media)) {
            return ApiResponse::forbidden();
        }

        $this->mediaService->delete($media);

        return ApiResponse::success();
    }

    /**
     * Reorder media items.
     */
    public function reorder(ReorderMediaRequest $request): JsonResponse
    {
        $user = Auth::user();
        $orderedIds = $request->input('ordered_ids');
        $collection = $request->input('collection');

        // Get the first media to determine the model
        $firstMedia = Media::find($orderedIds[0]);
        if (!$firstMedia) {
            return ApiResponse::notFound('Media not found');
        }

        $model = $firstMedia->model;
        if (!$this->canManageMedia($user, $model)) {
            return ApiResponse::forbidden();
        }

        $this->mediaService->reorder($model, $orderedIds, $collection);

        return ApiResponse::success();
    }

    /**
     * Get remaining slots for a collection.
     */
    public function remainingSlots(Request $request): JsonResponse
    {
        $modelType = $request->input('model_type');
        $modelId = $request->input('model_id');
        $collection = $request->input('collection');

        $model = $this->resolveModel($modelType, $modelId);
        if (!$model) {
            return ApiResponse::notFound('Model not found');
        }

        $remaining = $this->mediaService->getRemainingSlots($model, $collection);

        return ApiResponse::success([
            'remaining' => $remaining,
            'unlimited' => $remaining === null,
        ]);
    }

    /**
     * Check if user can delete a media item.
     */
    private function canDeleteMedia($user, Media $media): bool
    {
        $model = $media->model;
        return $this->canManageMedia($user, $model);
    }

    /**
     * Check if user can manage media for a model.
     */
    private function canManageMedia($user, Model $model): bool
    {
        return match (get_class($model)) {
            Provider::class => $user->provider?->id === $model->id,
            Service::class => $user->provider?->id === $model->provider_id,
            Review::class => $user->id === $model->user_id,
            default => false,
        };
    }

    /**
     * Resolve a model from type and ID.
     */
    private function resolveModel(string $type, int $id): ?Model
    {
        return match ($type) {
            'provider' => Provider::find($id),
            'service' => Service::find($id),
            'review' => Review::find($id),
            default => null,
        };
    }
}
