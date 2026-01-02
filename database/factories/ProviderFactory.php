<?php

namespace Database\Factories;

use App\Domains\Industry\Models\Industry;
use App\Domains\Provider\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    /**
     * Business name templates for realistic providers.
     */
    protected array $businessNameTemplates = [
        'salon' => [
            '{name} Salon & Spa',
            '{name} Beauty Studio',
            'The {adjective} Cut',
            '{name} Hair Atelier',
            'Studio {name}',
            '{adjective} Beauty Lounge',
            '{name} Style House',
        ],
        'spa' => [
            '{name} Wellness Spa',
            'Serenity by {name}',
            '{adjective} Day Spa',
            'The {adjective} Retreat',
            '{name} Holistic Wellness',
            'Pure {adjective} Spa',
        ],
        'barbershop' => [
            "{name}'s Barbershop",
            'The {adjective} Barber',
            '{name} Cuts',
            '{adjective} Fades',
            'Classic Cuts by {name}',
        ],
        'nails' => [
            '{name} Nail Studio',
            '{adjective} Nails & Beauty',
            'The Nail Bar by {name}',
            '{name} Beauty Nails',
        ],
        'fitness' => [
            '{name} Fitness Studio',
            '{adjective} Fitness',
            'Train with {name}',
            '{name} Personal Training',
        ],
        'default' => [
            '{name} Services',
            '{adjective} {type}',
            '{name} Professional Services',
        ],
    ];

    protected array $adjectives = [
        'Urban', 'Elite', 'Premier', 'Royal', 'Golden', 'Classic', 'Modern',
        'Elegant', 'Luxe', 'Signature', 'Fresh', 'Vibrant', 'Radiant', 'Pure',
    ];

    protected array $taglines = [
        'Where style meets excellence',
        'Your beauty, our passion',
        'Experience the difference',
        'Transforming beauty daily',
        'Excellence in every detail',
        'Where confidence begins',
        'Your journey to beauty starts here',
        'Professional care, stunning results',
        'Elevate your style',
        'Beauty reimagined',
    ];

    public function definition(): array
    {
        $businessName = $this->generateBusinessName();
        $firstName = fake()->firstName();

        return [
            'uuid' => Str::uuid()->toString(),
            'user_id' => User::factory(),
            'industry_id' => Industry::inRandomOrder()->first()?->id,
            'business_name' => $businessName,
            'slug' => Provider::generateSlug($businessName),
            'domain' => Provider::generateDomain($businessName),
            'bio' => fake()->paragraphs(2, true),
            'tagline' => fake()->randomElement($this->taglines),
            'address' => fake()->address(),
            'website' => fake()->optional(0.3)->url(),
            'social_links' => $this->generateSocialLinks(),
            'status' => 'active',
            'commission_rate' => fake()->randomFloat(2, 5, 15),
            'is_featured' => false,
            'requires_approval' => fake()->boolean(20),
            'deposit_type' => fake()->randomElement(['none', 'fixed', 'percentage']),
            'deposit_amount' => fake()->randomFloat(2, 10, 50),
            'deposit_percentage' => fake()->randomFloat(2, 10, 30),
            'cancellation_policy' => fake()->randomElement(['flexible', 'moderate', 'strict']),
            'advance_booking_days' => fake()->randomElement([14, 30, 60, 90]),
            'min_booking_notice_hours' => fake()->randomElement([1, 2, 4, 24, 48]),
            'fee_payer' => fake()->randomElement(['client', 'provider']),
            'buffer_minutes' => fake()->randomElement([0, 5, 10, 15, 30]),
            'is_founding_member' => false,
            'founding_member_at' => null,
            'branding' => $this->generateBranding(),
            'site_template' => 'default',
            'verified_at' => now(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'updated_at' => now(),
        ];
    }

    protected function generateBusinessName(?string $category = null): string
    {
        $templates = $this->businessNameTemplates[$category ?? 'default']
            ?? $this->businessNameTemplates['default'];

        $template = fake()->randomElement($templates);
        $name = fake()->firstName();
        $adjective = fake()->randomElement($this->adjectives);

        return str_replace(
            ['{name}', '{adjective}', '{type}'],
            [$name, $adjective, ucfirst($category ?? 'Services')],
            $template
        );
    }

    protected function generateSocialLinks(): array
    {
        $links = [];

        if (fake()->boolean(70)) {
            $links['instagram'] = 'https://instagram.com/' . fake()->userName();
        }
        if (fake()->boolean(50)) {
            $links['facebook'] = 'https://facebook.com/' . fake()->userName();
        }
        if (fake()->boolean(30)) {
            $links['twitter'] = 'https://twitter.com/' . fake()->userName();
        }
        if (fake()->boolean(20)) {
            $links['tiktok'] = 'https://tiktok.com/@' . fake()->userName();
        }

        return $links;
    }

    protected function generateBranding(): array
    {
        $colors = [
            '#106B4F', '#1a7a5c', '#0d5941', '#147a5a', '#0e6347', // Brand greens
            '#3B82F6', '#2563EB', '#1D4ED8', // Blues
            '#8B5CF6', '#7C3AED', '#6D28D9', // Purples
            '#EC4899', '#DB2777', '#BE185D', // Pinks
            '#F59E0B', '#D97706', '#B45309', // Oranges
        ];

        return [
            'primary_color' => fake()->randomElement($colors),
            'color_mode' => fake()->randomElement(['light', 'dark', 'system']),
        ];
    }

    // =========================================================================
    // States
    // =========================================================================

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'verified_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'verified_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'suspended',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
            'status' => 'active',
        ]);
    }

    public function foundingMember(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_founding_member' => true,
            'founding_member_at' => now()->subMonths(fake()->numberBetween(1, 6)),
        ]);
    }

    public function forIndustry(Industry $industry): static
    {
        return $this->state(fn (array $attributes) => [
            'industry_id' => $industry->id,
            'business_name' => $this->generateBusinessName($industry->slug),
        ]);
    }

    public function withUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }

    public function forSalon(): static
    {
        return $this->state(function (array $attributes) {
            $businessName = $this->generateBusinessName('salon');

            return [
                'business_name' => $businessName,
                'slug' => Provider::generateSlug($businessName),
                'domain' => Provider::generateDomain($businessName),
            ];
        });
    }

    public function forSpa(): static
    {
        return $this->state(function (array $attributes) {
            $businessName = $this->generateBusinessName('spa');

            return [
                'business_name' => $businessName,
                'slug' => Provider::generateSlug($businessName),
                'domain' => Provider::generateDomain($businessName),
            ];
        });
    }

    public function forBarbershop(): static
    {
        return $this->state(function (array $attributes) {
            $businessName = $this->generateBusinessName('barbershop');

            return [
                'business_name' => $businessName,
                'slug' => Provider::generateSlug($businessName),
                'domain' => Provider::generateDomain($businessName),
            ];
        });
    }
}
