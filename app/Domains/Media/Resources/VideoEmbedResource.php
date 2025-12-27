<?php

namespace App\Domains\Media\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VideoEmbedResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
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
