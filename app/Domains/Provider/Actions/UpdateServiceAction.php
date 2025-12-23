<?php

namespace App\Domains\Provider\Actions;

use App\Domains\Service\Models\Service;

class UpdateServiceAction
{
    public function execute(Service $service, array $data): Service
    {
        $service->update([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'],
            'price' => $data['price'],
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? $service->sort_order,
        ]);

        return $service->fresh('category');
    }
}
