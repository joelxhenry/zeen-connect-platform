<?php

namespace App\Domains\Provider\Enums;

/**
 * Team member permissions.
 *
 * These permissions control what team members can access within a provider's console.
 * The owner always has all permissions implicitly.
 */
final class TeamPermission
{
    // Dashboard
    public const VIEW_DASHBOARD = 'view_dashboard';

    // Bookings
    public const VIEW_BOOKINGS = 'view_bookings';
    public const MANAGE_BOOKINGS = 'manage_bookings';

    // Services
    public const VIEW_SERVICES = 'view_services';
    public const MANAGE_SERVICES = 'manage_services';

    // Payments
    public const VIEW_PAYMENTS = 'view_payments';

    // Availability
    public const MANAGE_AVAILABILITY = 'manage_availability';

    // Reviews
    public const VIEW_REVIEWS = 'view_reviews';
    public const RESPOND_REVIEWS = 'respond_reviews';

    // Profile
    public const MANAGE_PROFILE = 'manage_profile';

    // Team
    public const MANAGE_TEAM = 'manage_team';

    /**
     * Get all available permissions with their details.
     *
     * @return array<string, array{key: string, label: string, description: string, group: string}>
     */
    public static function all(): array
    {
        return [
            self::VIEW_DASHBOARD => [
                'key' => self::VIEW_DASHBOARD,
                'label' => 'View Dashboard',
                'description' => 'View dashboard statistics and overview',
                'group' => 'Dashboard',
            ],
            self::VIEW_BOOKINGS => [
                'key' => self::VIEW_BOOKINGS,
                'label' => 'View Bookings',
                'description' => 'View booking list and details',
                'group' => 'Bookings',
            ],
            self::MANAGE_BOOKINGS => [
                'key' => self::MANAGE_BOOKINGS,
                'label' => 'Manage Bookings',
                'description' => 'Confirm, cancel, and update bookings',
                'group' => 'Bookings',
            ],
            self::VIEW_SERVICES => [
                'key' => self::VIEW_SERVICES,
                'label' => 'View Services',
                'description' => 'View services list and details',
                'group' => 'Services',
            ],
            self::MANAGE_SERVICES => [
                'key' => self::MANAGE_SERVICES,
                'label' => 'Manage Services',
                'description' => 'Create, edit, and delete services',
                'group' => 'Services',
            ],
            self::VIEW_PAYMENTS => [
                'key' => self::VIEW_PAYMENTS,
                'label' => 'View Payments',
                'description' => 'View payments and payout history',
                'group' => 'Payments',
            ],
            self::MANAGE_AVAILABILITY => [
                'key' => self::MANAGE_AVAILABILITY,
                'label' => 'Manage Availability',
                'description' => 'Update schedule and blocked dates',
                'group' => 'Availability',
            ],
            self::VIEW_REVIEWS => [
                'key' => self::VIEW_REVIEWS,
                'label' => 'View Reviews',
                'description' => 'View customer reviews',
                'group' => 'Reviews',
            ],
            self::RESPOND_REVIEWS => [
                'key' => self::RESPOND_REVIEWS,
                'label' => 'Respond to Reviews',
                'description' => 'Post responses to customer reviews',
                'group' => 'Reviews',
            ],
            self::MANAGE_PROFILE => [
                'key' => self::MANAGE_PROFILE,
                'label' => 'Manage Profile',
                'description' => 'Edit provider profile information',
                'group' => 'Profile',
            ],
            self::MANAGE_TEAM => [
                'key' => self::MANAGE_TEAM,
                'label' => 'Manage Team',
                'description' => 'Invite, edit, and remove team members',
                'group' => 'Team',
            ],
        ];
    }

    /**
     * Get all permission keys.
     *
     * @return array<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * Get permissions grouped by category.
     *
     * @return array<string, array<string, array{key: string, label: string, description: string, group: string}>>
     */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $key => $permission) {
            $grouped[$permission['group']][$key] = $permission;
        }

        return $grouped;
    }

    /**
     * Get default permissions for new team members.
     *
     * @return array<string>
     */
    public static function defaults(): array
    {
        return [
            self::VIEW_DASHBOARD,
            self::VIEW_BOOKINGS,
            self::VIEW_SERVICES,
            self::VIEW_REVIEWS,
        ];
    }

    /**
     * Get permissions for a "staff" preset (booking-focused).
     *
     * @return array<string>
     */
    public static function staffPreset(): array
    {
        return [
            self::VIEW_DASHBOARD,
            self::VIEW_BOOKINGS,
            self::MANAGE_BOOKINGS,
            self::VIEW_SERVICES,
            self::VIEW_REVIEWS,
            self::MANAGE_AVAILABILITY,
        ];
    }

    /**
     * Get permissions for an "admin" preset (full access except billing).
     *
     * @return array<string>
     */
    public static function adminPreset(): array
    {
        return [
            self::VIEW_DASHBOARD,
            self::VIEW_BOOKINGS,
            self::MANAGE_BOOKINGS,
            self::VIEW_SERVICES,
            self::MANAGE_SERVICES,
            self::VIEW_PAYMENTS,
            self::MANAGE_AVAILABILITY,
            self::VIEW_REVIEWS,
            self::RESPOND_REVIEWS,
            self::MANAGE_PROFILE,
            self::MANAGE_TEAM,
        ];
    }

    /**
     * Get permissions for a "viewer" preset (read-only).
     *
     * @return array<string>
     */
    public static function viewerPreset(): array
    {
        return [
            self::VIEW_DASHBOARD,
            self::VIEW_BOOKINGS,
            self::VIEW_SERVICES,
            self::VIEW_REVIEWS,
        ];
    }

    /**
     * Get all preset options for the UI.
     *
     * @return array<string, array{label: string, description: string, permissions: array<string>}>
     */
    public static function presets(): array
    {
        return [
            'viewer' => [
                'label' => 'Viewer',
                'description' => 'Can only view data, no modifications',
                'permissions' => self::viewerPreset(),
            ],
            'staff' => [
                'label' => 'Staff',
                'description' => 'Can manage bookings and availability',
                'permissions' => self::staffPreset(),
            ],
            'admin' => [
                'label' => 'Admin',
                'description' => 'Full access except billing settings',
                'permissions' => self::adminPreset(),
            ],
        ];
    }

    /**
     * Check if a permission key is valid.
     */
    public static function isValid(string $permission): bool
    {
        return array_key_exists($permission, self::all());
    }

    /**
     * Validate an array of permissions.
     *
     * @param  array<string>  $permissions
     * @return array<string>  Only valid permissions
     */
    public static function validate(array $permissions): array
    {
        return array_values(array_filter($permissions, fn ($p) => self::isValid($p)));
    }
}
