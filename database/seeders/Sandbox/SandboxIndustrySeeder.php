<?php

namespace Database\Seeders\Sandbox;

use App\Domains\Industry\Models\Industry;
use Illuminate\Database\Seeder;

class SandboxIndustrySeeder extends Seeder
{
    /**
     * Industries and their categories are seeded by ProductionSeeder.
     * This seeder ensures they exist and adds any missing ones.
     */
    public function run(): void
    {
        // Industries should already be seeded by ProductionSeeder
        // This seeder is a safeguard to ensure they exist

        $industries = [
            [
                'name' => 'Beauty & Wellness',
                'slug' => 'beauty-wellness',
                'description' => 'Hair salons, spas, nail studios, and wellness services',
            ],
            [
                'name' => 'Health & Fitness',
                'slug' => 'health-fitness',
                'description' => 'Gyms, personal training, yoga studios, and fitness services',
            ],
            [
                'name' => 'Professional Services',
                'slug' => 'professional-services',
                'description' => 'Consulting, coaching, and professional appointments',
            ],
        ];

        foreach ($industries as $industryData) {
            Industry::firstOrCreate(
                ['slug' => $industryData['slug']],
                $industryData
            );
        }
    }
}
