<?php

namespace Database\Factories;

use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    /**
     * Service templates by category for realistic data.
     */
    protected array $serviceTemplates = [
        'haircut' => [
            ['name' => "Women's Haircut", 'duration' => 45, 'price_range' => [35, 80]],
            ['name' => "Men's Haircut", 'duration' => 30, 'price_range' => [25, 45]],
            ['name' => "Kids Haircut", 'duration' => 20, 'price_range' => [15, 30]],
            ['name' => 'Bang Trim', 'duration' => 15, 'price_range' => [10, 20]],
            ['name' => 'Haircut & Style', 'duration' => 60, 'price_range' => [50, 100]],
        ],
        'color' => [
            ['name' => 'Full Color', 'duration' => 90, 'price_range' => [75, 150]],
            ['name' => 'Highlights', 'duration' => 120, 'price_range' => [100, 200]],
            ['name' => 'Balayage', 'duration' => 150, 'price_range' => [150, 300]],
            ['name' => 'Root Touch Up', 'duration' => 60, 'price_range' => [50, 90]],
            ['name' => 'Color Correction', 'duration' => 180, 'price_range' => [200, 400]],
            ['name' => 'Gloss Treatment', 'duration' => 45, 'price_range' => [40, 80]],
        ],
        'styling' => [
            ['name' => 'Blowout', 'duration' => 45, 'price_range' => [35, 60]],
            ['name' => 'Updo', 'duration' => 60, 'price_range' => [65, 120]],
            ['name' => 'Bridal Hair', 'duration' => 90, 'price_range' => [150, 300]],
            ['name' => 'Special Event Styling', 'duration' => 75, 'price_range' => [80, 150]],
        ],
        'treatment' => [
            ['name' => 'Deep Conditioning Treatment', 'duration' => 30, 'price_range' => [25, 50]],
            ['name' => 'Keratin Treatment', 'duration' => 120, 'price_range' => [200, 400]],
            ['name' => 'Scalp Treatment', 'duration' => 30, 'price_range' => [35, 60]],
            ['name' => 'Olaplex Treatment', 'duration' => 30, 'price_range' => [40, 75]],
        ],
        'nails' => [
            ['name' => 'Classic Manicure', 'duration' => 30, 'price_range' => [20, 35]],
            ['name' => 'Classic Pedicure', 'duration' => 45, 'price_range' => [30, 50]],
            ['name' => 'Gel Manicure', 'duration' => 45, 'price_range' => [35, 55]],
            ['name' => 'Acrylic Full Set', 'duration' => 60, 'price_range' => [50, 80]],
            ['name' => 'Nail Art', 'duration' => 30, 'price_range' => [15, 40]],
        ],
        'spa' => [
            ['name' => 'Swedish Massage', 'duration' => 60, 'price_range' => [70, 120]],
            ['name' => 'Deep Tissue Massage', 'duration' => 60, 'price_range' => [80, 140]],
            ['name' => 'Hot Stone Massage', 'duration' => 75, 'price_range' => [100, 160]],
            ['name' => 'Facial', 'duration' => 60, 'price_range' => [65, 120]],
            ['name' => 'Body Wrap', 'duration' => 90, 'price_range' => [90, 150]],
        ],
        'barber' => [
            ['name' => 'Classic Haircut', 'duration' => 30, 'price_range' => [20, 35]],
            ['name' => 'Fade', 'duration' => 30, 'price_range' => [25, 40]],
            ['name' => 'Beard Trim', 'duration' => 15, 'price_range' => [10, 20]],
            ['name' => 'Hot Towel Shave', 'duration' => 30, 'price_range' => [25, 45]],
            ['name' => 'Haircut & Beard', 'duration' => 45, 'price_range' => [35, 55]],
        ],
        'makeup' => [
            ['name' => 'Full Makeup Application', 'duration' => 60, 'price_range' => [60, 120]],
            ['name' => 'Bridal Makeup', 'duration' => 90, 'price_range' => [150, 300]],
            ['name' => 'Makeup Lesson', 'duration' => 60, 'price_range' => [75, 150]],
            ['name' => 'Lash Application', 'duration' => 30, 'price_range' => [20, 40]],
        ],
        'waxing' => [
            ['name' => 'Eyebrow Wax', 'duration' => 15, 'price_range' => [10, 20]],
            ['name' => 'Lip Wax', 'duration' => 10, 'price_range' => [8, 15]],
            ['name' => 'Full Face Wax', 'duration' => 30, 'price_range' => [30, 50]],
            ['name' => 'Full Leg Wax', 'duration' => 45, 'price_range' => [50, 80]],
            ['name' => 'Brazilian Wax', 'duration' => 30, 'price_range' => [45, 75]],
        ],
    ];

    public function definition(): array
    {
        // Pick a random service template
        $category = fake()->randomElement(array_keys($this->serviceTemplates));
        $template = fake()->randomElement($this->serviceTemplates[$category]);

        return [
            'uuid' => Str::uuid()->toString(),
            'provider_id' => Provider::factory(),
            'name' => $template['name'],
            'description' => $this->generateDescription($template['name']),
            'duration_minutes' => $template['duration'],
            'price' => fake()->randomFloat(2, $template['price_range'][0], $template['price_range'][1]),
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 100),
            'settings' => [
                'use_provider_defaults' => fake()->boolean(80),
            ],
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    protected function generateDescription(string $serviceName): string
    {
        $descriptions = [
            "Experience our professional {$serviceName} service, delivered by our skilled team members.",
            "Our {$serviceName} is designed to leave you feeling refreshed and looking your best.",
            "Treat yourself to our signature {$serviceName} for a luxurious experience.",
            "Indulge in our {$serviceName} service, tailored to your specific needs.",
            "Our expert {$serviceName} will help you achieve the look you've always wanted.",
        ];

        return fake()->randomElement($descriptions);
    }

    // =========================================================================
    // States
    // =========================================================================

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => $provider->id,
        ]);
    }

    public function haircut(): static
    {
        return $this->fromCategory('haircut');
    }

    public function color(): static
    {
        return $this->fromCategory('color');
    }

    public function styling(): static
    {
        return $this->fromCategory('styling');
    }

    public function nails(): static
    {
        return $this->fromCategory('nails');
    }

    public function spa(): static
    {
        return $this->fromCategory('spa');
    }

    public function barber(): static
    {
        return $this->fromCategory('barber');
    }

    public function makeup(): static
    {
        return $this->fromCategory('makeup');
    }

    public function waxing(): static
    {
        return $this->fromCategory('waxing');
    }

    public function fromCategory(string $category): static
    {
        return $this->state(function (array $attributes) use ($category) {
            $templates = $this->serviceTemplates[$category] ?? $this->serviceTemplates['haircut'];
            $template = fake()->randomElement($templates);

            return [
                'name' => $template['name'],
                'description' => $this->generateDescription($template['name']),
                'duration_minutes' => $template['duration'],
                'price' => fake()->randomFloat(2, $template['price_range'][0], $template['price_range'][1]),
            ];
        });
    }

    public function withCustomBookingSettings(): static
    {
        return $this->state(fn (array $attributes) => [
            'settings' => [
                'use_provider_defaults' => false,
                'requires_approval' => fake()->boolean(30),
                'deposit_type' => fake()->randomElement(['none', 'fixed', 'percentage']),
                'deposit_amount' => fake()->randomFloat(2, 10, 50),
                'cancellation_policy' => fake()->randomElement(['flexible', 'moderate', 'strict']),
                'advance_booking_days' => fake()->randomElement([14, 30, 60]),
                'min_booking_notice_hours' => fake()->randomElement([1, 2, 4, 24]),
                'buffer_minutes' => fake()->randomElement([0, 5, 10, 15]),
            ],
        ]);
    }

    /**
     * Create a set of services for a provider with a given count.
     */
    public static function createServicesForProvider(Provider $provider, int $count = 5): \Illuminate\Database\Eloquent\Collection
    {
        $services = collect();
        $categories = array_keys((new self)->serviceTemplates);
        $usedTemplates = [];

        for ($i = 0; $i < $count; $i++) {
            $category = fake()->randomElement($categories);
            $templates = (new self)->serviceTemplates[$category];
            $template = fake()->randomElement($templates);

            // Avoid duplicate services
            $key = $template['name'];
            if (isset($usedTemplates[$key])) {
                continue;
            }
            $usedTemplates[$key] = true;

            $service = Service::factory()
                ->forProvider($provider)
                ->fromCategory($category)
                ->create([
                    'sort_order' => $i + 1,
                ]);

            $services->push($service);
        }

        return $services;
    }
}
