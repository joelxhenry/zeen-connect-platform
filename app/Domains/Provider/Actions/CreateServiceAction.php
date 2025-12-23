<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;

class CreateServiceAction
{
    public function execute(Provider $provider, array $data): Service
    {
        return $provider->services()->create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'price' => $data['price'],
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }
}
