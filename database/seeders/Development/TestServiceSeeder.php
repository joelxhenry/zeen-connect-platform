<?php

namespace Database\Seeders\Development;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TestServiceSeeder extends Seeder
{
    /**
     * Seed test services for each test provider.
     */
    public function run(): void
    {
        $now = now();

        // Get provider IDs by domain
        $providers = DB::table('providers')
            ->whereIn('domain', ['starter-styles', 'premium-wellness', 'enterprise-beauty'])
            ->pluck('id', 'domain')
            ->toArray();

        if (empty($providers)) {
            $this->command->warn('No test providers found. Run TestProviderSeeder first.');

            return;
        }

        // Get category IDs by slug
        $categories = DB::table('categories')
            ->pluck('id', 'slug')
            ->toArray();

        if (empty($categories)) {
            $this->command->warn('No categories found. Run CategorySeeder first.');

            return;
        }

        $servicesCreated = 0;

        // Starter Styles Salon - Basic hair services
        if (isset($providers['starter-styles']) && isset($categories['hair'])) {
            $servicesCreated += $this->seedProviderServices(
                $providers['starter-styles'],
                $this->getStarterServices($categories),
                $now
            );
        }

        // Premium Wellness Spa - Spa and beauty services
        if (isset($providers['premium-wellness'])) {
            $servicesCreated += $this->seedProviderServices(
                $providers['premium-wellness'],
                $this->getPremiumServices($categories),
                $now
            );
        }

        // Enterprise Beauty Group - Full range of services
        if (isset($providers['enterprise-beauty'])) {
            $servicesCreated += $this->seedProviderServices(
                $providers['enterprise-beauty'],
                $this->getEnterpriseServices($categories),
                $now
            );
        }

        $this->command->info("Test services seeded: {$servicesCreated} services");
    }

    private function seedProviderServices(int $providerId, array $services, $now): int
    {
        $count = 0;

        foreach ($services as $index => $service) {
            $existing = DB::table('services')
                ->where('provider_id', $providerId)
                ->where('name', $service['name'])
                ->first();

            if (! $existing) {
                DB::table('services')->insert([
                    'uuid' => Str::uuid()->toString(),
                    'provider_id' => $providerId,
                    'category_id' => $service['category_id'],
                    'name' => $service['name'],
                    'description' => $service['description'],
                    'duration_minutes' => $service['duration_minutes'],
                    'price' => $service['price'],
                    'is_active' => true,
                    'sort_order' => $index,
                    'use_provider_defaults' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $count++;
            } else {
                DB::table('services')->where('id', $existing->id)->update([
                    'category_id' => $service['category_id'],
                    'description' => $service['description'],
                    'duration_minutes' => $service['duration_minutes'],
                    'price' => $service['price'],
                    'updated_at' => $now,
                ]);
            }
        }

        return $count;
    }

    private function getStarterServices(array $categories): array
    {
        $hairId = $categories['hair'] ?? 1;

        return [
            [
                'name' => 'Basic Haircut',
                'description' => 'A classic haircut tailored to your style. Includes wash and blow dry.',
                'category_id' => $hairId,
                'duration_minutes' => 30,
                'price' => 2500.00,
            ],
            [
                'name' => 'Haircut & Style',
                'description' => 'Full haircut with professional styling. Perfect for a polished look.',
                'category_id' => $hairId,
                'duration_minutes' => 45,
                'price' => 3500.00,
            ],
            [
                'name' => 'Wash & Blow Dry',
                'description' => 'Relaxing hair wash followed by a professional blow dry.',
                'category_id' => $hairId,
                'duration_minutes' => 30,
                'price' => 1500.00,
            ],
            [
                'name' => 'Deep Conditioning Treatment',
                'description' => 'Intensive conditioning treatment to restore moisture and shine.',
                'category_id' => $hairId,
                'duration_minutes' => 45,
                'price' => 2000.00,
            ],
        ];
    }

    private function getPremiumServices(array $categories): array
    {
        $spaId = $categories['massage-spa'] ?? 5;
        $beautyId = $categories['beauty-makeup'] ?? 2;

        return [
            // Massage & Spa
            [
                'name' => 'Swedish Massage',
                'description' => 'A relaxing full-body massage using gentle strokes to ease tension and promote relaxation.',
                'category_id' => $spaId,
                'duration_minutes' => 60,
                'price' => 8000.00,
            ],
            [
                'name' => 'Deep Tissue Massage',
                'description' => 'Intensive massage targeting deep muscle layers to relieve chronic tension.',
                'category_id' => $spaId,
                'duration_minutes' => 60,
                'price' => 10000.00,
            ],
            [
                'name' => 'Hot Stone Therapy',
                'description' => 'Heated stones placed on key points to melt away stress and tension.',
                'category_id' => $spaId,
                'duration_minutes' => 75,
                'price' => 12000.00,
            ],
            [
                'name' => 'Couples Massage',
                'description' => 'Share a relaxing massage experience with your partner in our couples suite.',
                'category_id' => $spaId,
                'duration_minutes' => 60,
                'price' => 15000.00,
            ],
            // Beauty
            [
                'name' => 'Signature Facial',
                'description' => 'Customized facial treatment to address your specific skin concerns.',
                'category_id' => $beautyId,
                'duration_minutes' => 60,
                'price' => 7500.00,
            ],
            [
                'name' => 'Anti-Aging Facial',
                'description' => 'Advanced treatment using premium products to reduce fine lines and restore youthful glow.',
                'category_id' => $beautyId,
                'duration_minutes' => 75,
                'price' => 12000.00,
            ],
        ];
    }

    private function getEnterpriseServices(array $categories): array
    {
        $hairId = $categories['hair'] ?? 1;
        $beautyId = $categories['beauty-makeup'] ?? 2;
        $nailsId = $categories['nails'] ?? 3;
        $spaId = $categories['massage-spa'] ?? 5;

        return [
            // Hair Services
            [
                'name' => 'Executive Haircut',
                'description' => 'Premium haircut service with consultation, wash, cut, and styling.',
                'category_id' => $hairId,
                'duration_minutes' => 45,
                'price' => 5000.00,
            ],
            [
                'name' => 'Color & Highlights',
                'description' => 'Professional hair coloring with highlights for a dimensional look.',
                'category_id' => $hairId,
                'duration_minutes' => 120,
                'price' => 15000.00,
            ],
            [
                'name' => 'Keratin Treatment',
                'description' => 'Smoothing treatment that eliminates frizz and adds shine for up to 3 months.',
                'category_id' => $hairId,
                'duration_minutes' => 180,
                'price' => 25000.00,
            ],
            // Beauty Services
            [
                'name' => 'Bridal Makeup',
                'description' => 'Complete bridal makeup with trial session included. Look stunning on your special day.',
                'category_id' => $beautyId,
                'duration_minutes' => 120,
                'price' => 20000.00,
            ],
            [
                'name' => 'Hydrafacial',
                'description' => 'Multi-step treatment that cleanses, exfoliates, extracts, and hydrates.',
                'category_id' => $beautyId,
                'duration_minutes' => 60,
                'price' => 15000.00,
            ],
            // Nail Services
            [
                'name' => 'Luxury Manicure',
                'description' => 'Full manicure with exfoliation, massage, and premium polish.',
                'category_id' => $nailsId,
                'duration_minutes' => 45,
                'price' => 3500.00,
            ],
            [
                'name' => 'Gel Manicure',
                'description' => 'Long-lasting gel polish application with nail shaping and cuticle care.',
                'category_id' => $nailsId,
                'duration_minutes' => 60,
                'price' => 5000.00,
            ],
            [
                'name' => 'Spa Pedicure',
                'description' => 'Relaxing foot treatment with soak, exfoliation, massage, and polish.',
                'category_id' => $nailsId,
                'duration_minutes' => 60,
                'price' => 4500.00,
            ],
            // Spa Services
            [
                'name' => 'Executive Stress Relief',
                'description' => 'Targeted massage focusing on neck, shoulders, and back to relieve work-related tension.',
                'category_id' => $spaId,
                'duration_minutes' => 45,
                'price' => 7000.00,
            ],
            [
                'name' => 'Full Day Spa Package',
                'description' => 'Complete spa day including massage, facial, manicure, and pedicure. Lunch included.',
                'category_id' => $spaId,
                'duration_minutes' => 300,
                'price' => 45000.00,
            ],
        ];
    }
}
