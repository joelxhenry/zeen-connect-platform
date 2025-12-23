<?php

namespace Database\Seeders;

use App\Domains\Service\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Hair',
                'icon' => 'pi-scissors',
                'description' => 'Haircuts, styling, coloring, and hair treatments',
            ],
            [
                'name' => 'Beauty & Makeup',
                'icon' => 'pi-heart',
                'description' => 'Makeup, facials, skincare, and beauty treatments',
            ],
            [
                'name' => 'Nails',
                'icon' => 'pi-palette',
                'description' => 'Manicures, pedicures, nail art, and nail care',
            ],
            [
                'name' => 'Barbering',
                'icon' => 'pi-user',
                'description' => 'Haircuts, beard trims, and grooming for men',
            ],
            [
                'name' => 'Massage & Spa',
                'icon' => 'pi-sun',
                'description' => 'Massage therapy, spa treatments, and relaxation',
            ],
            [
                'name' => 'Photography',
                'icon' => 'pi-camera',
                'description' => 'Portrait, event, and professional photography',
            ],
            [
                'name' => 'Fitness & Training',
                'icon' => 'pi-bolt',
                'description' => 'Personal training, yoga, and fitness coaching',
            ],
            [
                'name' => 'Tutoring & Education',
                'icon' => 'pi-book',
                'description' => 'Academic tutoring, lessons, and educational services',
            ],
            [
                'name' => 'Home Services',
                'icon' => 'pi-home',
                'description' => 'Cleaning, repairs, and home maintenance',
            ],
            [
                'name' => 'Events & Entertainment',
                'icon' => 'pi-star',
                'description' => 'Event planning, DJ, MC, and entertainment services',
            ],
            [
                'name' => 'Automotive',
                'icon' => 'pi-car',
                'description' => 'Car wash, detailing, and automotive services',
            ],
            [
                'name' => 'Other Services',
                'icon' => 'pi-ellipsis-h',
                'description' => 'Other professional services',
            ],
        ];

        foreach ($categories as $index => $category) {
            Category::create([
                'uuid' => Str::uuid(),
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'icon' => $category['icon'],
                'description' => $category['description'],
                'is_active' => true,
                'sort_order' => $index,
            ]);
        }

        $this->command->info('Categories seeded: ' . count($categories) . ' categories');
    }
}
