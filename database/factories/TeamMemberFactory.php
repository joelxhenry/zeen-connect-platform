<?php

namespace Database\Factories;

use App\Domains\Provider\Enums\TeamMemberStatus;
use App\Domains\Provider\Enums\TeamPermission;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    protected array $titles = [
        'Senior Stylist',
        'Junior Stylist',
        'Colorist',
        'Nail Technician',
        'Massage Therapist',
        'Esthetician',
        'Barber',
        'Receptionist',
        'Manager',
        'Assistant',
    ];

    /**
     * Permission presets for team members.
     */
    protected array $permissionPresets = [
        'viewer' => [
            'bookings.view',
            'services.view',
            'schedule.view',
        ],
        'staff' => [
            'bookings.view',
            'bookings.manage',
            'services.view',
            'schedule.view',
            'schedule.manage',
            'clients.view',
        ],
        'admin' => [
            'bookings.view',
            'bookings.manage',
            'services.view',
            'services.manage',
            'schedule.view',
            'schedule.manage',
            'clients.view',
            'clients.manage',
            'team.view',
            'settings.view',
            'reports.view',
        ],
    ];

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'provider_id' => Provider::factory(),
            'user_id' => null,
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'title' => fake()->randomElement($this->titles),
            'permissions' => $this->permissionPresets['staff'],
            'status' => TeamMemberStatus::PENDING,
            'invitation_token' => Str::random(64),
            'invited_at' => now(),
            'accepted_at' => null,
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'updated_at' => now(),
        ];
    }

    // =========================================================================
    // Status States
    // =========================================================================

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TeamMemberStatus::PENDING,
            'user_id' => null,
            'invitation_token' => Str::random(64),
            'invited_at' => now()->subDays(fake()->numberBetween(1, 5)),
            'accepted_at' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TeamMemberStatus::ACTIVE,
            'user_id' => User::factory(),
            'invitation_token' => null,
            'accepted_at' => now()->subDays(fake()->numberBetween(1, 60)),
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TeamMemberStatus::SUSPENDED,
            'user_id' => User::factory(),
            'invitation_token' => null,
            'accepted_at' => now()->subDays(fake()->numberBetween(30, 90)),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TeamMemberStatus::PENDING,
            'user_id' => null,
            'invitation_token' => Str::random(64),
            'invited_at' => now()->subDays(10), // Expired after 7 days
            'accepted_at' => null,
        ]);
    }

    // =========================================================================
    // Permission States
    // =========================================================================

    public function viewer(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => $this->permissionPresets['viewer'],
        ]);
    }

    public function staff(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => $this->permissionPresets['staff'],
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => $this->permissionPresets['admin'],
        ]);
    }

    public function withPermissions(array $permissions): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => TeamPermission::validate($permissions),
        ]);
    }

    public function withRandomPermissionPreset(): static
    {
        return $this->state(function (array $attributes) {
            $preset = fake()->randomElement(['viewer', 'staff', 'admin']);
            $weights = [
                'viewer' => 30,
                'staff' => 40,
                'admin' => 30,
            ];

            // Weighted random selection
            $rand = fake()->numberBetween(1, 100);
            $cumulative = 0;
            foreach ($weights as $key => $weight) {
                $cumulative += $weight;
                if ($rand <= $cumulative) {
                    $preset = $key;
                    break;
                }
            }

            return [
                'permissions' => $this->permissionPresets[$preset],
            ];
        });
    }

    // =========================================================================
    // Relationship States
    // =========================================================================

    public function forProvider(Provider $provider): static
    {
        return $this->state(fn (array $attributes) => [
            'provider_id' => $provider->id,
        ]);
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'status' => TeamMemberStatus::ACTIVE,
            'invitation_token' => null,
            'accepted_at' => now(),
        ]);
    }

    // =========================================================================
    // Title States
    // =========================================================================

    public function asStylist(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => fake()->randomElement(['Senior Stylist', 'Junior Stylist', 'Stylist']),
        ]);
    }

    public function asManager(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Manager',
            'permissions' => $this->permissionPresets['admin'],
        ]);
    }

    public function asReceptionist(): static
    {
        return $this->state(fn (array $attributes) => [
            'title' => 'Receptionist',
            'permissions' => $this->permissionPresets['staff'],
        ]);
    }
}
