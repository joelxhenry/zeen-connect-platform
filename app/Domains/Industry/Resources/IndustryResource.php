<?php

namespace App\Domains\Industry\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IndustryResource extends JsonResource
{
    protected bool $includeCounts = false;

    /**
     * Include relationship counts.
     */
    public function withCounts(bool $include = true): self
    {
        $this->includeCounts = $include;

        return $this;
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'icon' => $this->icon,
            'description' => $this->description,
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
        ];

        if ($this->includeCounts) {
            $data['providers_count'] = $this->providers_count ?? $this->providers()->count();
        }

        return $data;
    }
}
