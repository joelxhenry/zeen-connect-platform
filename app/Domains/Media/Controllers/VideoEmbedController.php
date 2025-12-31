<?php

namespace App\Domains\Media\Controllers;

use App\Domains\Event\Models\Event;
use App\Domains\Media\Models\VideoEmbed;
use App\Domains\Media\Requests\AddVideoEmbedRequest;
use App\Domains\Media\Services\VideoEmbedService;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VideoEmbedController extends Controller
{
    public function __construct(
        private VideoEmbedService $videoService
    ) {}

    /**
     * Add a video embed to a provider.
     */
    public function addProviderVideo(AddVideoEmbedRequest $request): JsonResponse
    {
        $provider = Auth::user()->provider;

        if (!$provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        try {
            $video = $this->addVideo($provider, $request);

            return response()->json([
                'success' => true,
                'video' => $video->toVideoArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Add a video embed to a service.
     */
    public function addServiceVideo(AddVideoEmbedRequest $request, Service $service): JsonResponse
    {
        $provider = Auth::user()->provider;

        if (!$provider || $service->provider_id !== $provider->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $video = $this->addVideo($service, $request);

            return response()->json([
                'success' => true,
                'video' => $video->toVideoArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Add a video embed to an event.
     */
    public function addEventVideo(AddVideoEmbedRequest $request, Event $event): JsonResponse
    {
        $provider = Auth::user()->provider;

        if (!$provider || $event->provider_id !== $provider->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $video = $this->addVideo($event, $request);

            return response()->json([
                'success' => true,
                'video' => $video->toVideoArray(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Update a video embed.
     */
    public function update(Request $request, VideoEmbed $video): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageVideo($user, $video)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $video = $this->videoService->update($video, $request->only(['title', 'order']));

        return response()->json([
            'success' => true,
            'video' => $video->toVideoArray(),
        ]);
    }

    /**
     * Delete a video embed.
     */
    public function destroy(VideoEmbed $video): JsonResponse
    {
        $user = Auth::user();

        if (!$this->canManageVideo($user, $video)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->videoService->delete($video);

        return response()->json(['success' => true]);
    }

    /**
     * Reorder video embeds.
     */
    public function reorder(Request $request): JsonResponse
    {
        $user = Auth::user();
        $orderedIds = $request->input('ordered_ids', []);

        if (empty($orderedIds)) {
            return response()->json(['error' => 'No video IDs provided'], 422);
        }

        // Get the first video to determine the model
        $firstVideo = VideoEmbed::find($orderedIds[0]);
        if (!$firstVideo) {
            return response()->json(['error' => 'Video not found'], 404);
        }

        $model = $firstVideo->model;
        if (!$this->canManageModel($user, $model)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $this->videoService->reorder($model, $orderedIds);

        return response()->json(['success' => true]);
    }

    /**
     * Parse a video URL.
     */
    public function parseUrl(Request $request): JsonResponse
    {
        $url = $request->input('url');

        if (!$url) {
            return response()->json(['error' => 'URL is required'], 422);
        }

        $parsed = $this->videoService->parseVideoUrl($url);

        if (!$parsed) {
            return response()->json(['error' => 'Invalid video URL'], 422);
        }

        return response()->json([
            'success' => true,
            'platform' => $parsed['platform'],
            'video_id' => $parsed['video_id'],
            'embed_code' => $this->videoService->generateEmbedCode($url),
        ]);
    }

    /**
     * Add a video to a model.
     */
    private function addVideo(Model $model, AddVideoEmbedRequest $request): VideoEmbed
    {
        $title = $request->input('title');

        if ($request->has('url') && $request->input('url')) {
            return $this->videoService->addFromUrl($model, $request->input('url'), $title);
        }

        if ($request->has('embed_code') && $request->input('embed_code')) {
            return $this->videoService->addFromEmbedCode($model, $request->input('embed_code'), $title);
        }

        throw new \InvalidArgumentException('Either URL or embed code is required');
    }

    /**
     * Check if user can manage a video embed.
     */
    private function canManageVideo($user, VideoEmbed $video): bool
    {
        return $this->canManageModel($user, $video->model);
    }

    /**
     * Check if user can manage a model.
     */
    private function canManageModel($user, Model $model): bool
    {
        return match (get_class($model)) {
            Provider::class => $user->provider?->id === $model->id,
            Service::class => $user->provider?->id === $model->provider_id,
            Event::class => $user->provider?->id === $model->provider_id,
            default => false,
        };
    }
}
