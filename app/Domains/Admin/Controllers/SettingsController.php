<?php

namespace App\Domains\Admin\Controllers;

use App\Domains\Admin\Models\SystemSetting;
use App\Domains\Admin\Requests\UpdateSystemSettingsRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Settings categories with their keys and metadata.
     */
    private const SETTINGS_SCHEMA = [
        'launch_mode' => [
            'label' => 'Launch Mode',
            'description' => 'Control platform availability during pre-launch',
            'icon' => 'pi pi-rocket',
            'settings' => [
                'launch_mode_enabled' => [
                    'label' => 'Enable Launch Mode',
                    'type' => 'toggle',
                    'description' => 'When enabled, registration is disabled and users are directed to the waitlist',
                    'default' => false,
                ],
            ],
        ],
        'tier_pricing' => [
            'label' => 'Tier Pricing',
            'description' => 'Platform fee rates and subscription prices for each tier',
            'icon' => 'pi pi-dollar',
            'settings' => [
                'free_tier_platform_fee_rate' => [
                    'label' => 'Free Tier Platform Fee',
                    'type' => 'percentage',
                    'min' => 0,
                    'max' => 100,
                    'description' => 'Platform fee percentage for free tier providers',
                    'default' => 8,
                ],
                'premium_tier_platform_fee_rate' => [
                    'label' => 'Premium Tier Platform Fee',
                    'type' => 'percentage',
                    'min' => 0,
                    'max' => 100,
                    'description' => 'Platform fee percentage for premium tier providers',
                    'default' => 4,
                ],
                'premium_tier_monthly_price' => [
                    'label' => 'Premium Tier Monthly Price',
                    'type' => 'currency',
                    'min' => 0,
                    'description' => 'Monthly subscription price for Premium tier (JMD)',
                    'default' => 3500,
                ],
                'enterprise_tier_monthly_price' => [
                    'label' => 'Enterprise Tier Monthly Price',
                    'type' => 'currency',
                    'min' => 0,
                    'description' => 'Monthly subscription price for Enterprise tier (JMD)',
                    'default' => 20000,
                ],
            ],
        ],
        'deposits' => [
            'label' => 'Deposit Settings',
            'description' => 'Deposit requirements and minimums for each tier',
            'icon' => 'pi pi-wallet',
            'settings' => [
                'minimum_deposit_percentage' => [
                    'label' => 'Minimum Deposit Percentage',
                    'type' => 'percentage',
                    'min' => 0,
                    'max' => 100,
                    'description' => 'Minimum deposit percentage for premium tier providers',
                    'default' => 15,
                ],
                'free_tier_deposit_percentage' => [
                    'label' => 'Free Tier Deposit Percentage',
                    'type' => 'percentage',
                    'min' => 0,
                    'max' => 100,
                    'description' => 'Fixed deposit percentage for free tier (compulsory)',
                    'default' => 20,
                ],
                'minimum_deposit_amount' => [
                    'label' => 'Minimum Deposit Amount',
                    'type' => 'currency',
                    'min' => 0,
                    'description' => 'Mandatory minimum deposit amount for Free tier (JMD)',
                    'default' => 500,
                ],
            ],
        ],
        'processing' => [
            'label' => 'Processing Fees',
            'description' => 'Card processing fee configuration for Enterprise tier',
            'icon' => 'pi pi-credit-card',
            'settings' => [
                'enterprise_processing_fee_rate' => [
                    'label' => 'Processing Fee Rate',
                    'type' => 'percentage',
                    'min' => 0,
                    'max' => 10,
                    'step' => 0.1,
                    'description' => 'Card processing fee percentage (e.g., 2.9%)',
                    'default' => 2.9,
                ],
                'enterprise_processing_fee_flat' => [
                    'label' => 'Processing Fee Flat',
                    'type' => 'currency',
                    'min' => 0,
                    'description' => 'Flat card processing fee per transaction (JMD)',
                    'default' => 50,
                ],
            ],
        ],
        'no_show' => [
            'label' => 'No-Show Policy',
            'description' => 'How deposits are split when clients fail to show up',
            'icon' => 'pi pi-exclamation-triangle',
            'settings' => [
                'no_show_deposit_provider_percentage' => [
                    'label' => 'Provider Share',
                    'type' => 'percentage',
                    'min' => 0,
                    'max' => 100,
                    'description' => 'Percentage of deposit kept by provider on no-show',
                    'default' => 50,
                ],
                'no_show_deposit_platform_percentage' => [
                    'label' => 'Platform Share',
                    'type' => 'percentage',
                    'min' => 0,
                    'max' => 100,
                    'description' => 'Percentage of deposit kept by platform on no-show',
                    'default' => 50,
                ],
            ],
        ],
        'service_restrictions' => [
            'label' => 'Service Restrictions',
            'description' => 'Minimum service prices per subscription tier',
            'icon' => 'pi pi-shield',
            'settings' => [
                'starter_tier_minimum_service_price' => [
                    'label' => 'Starter Tier Minimum Service Price',
                    'type' => 'currency',
                    'min' => 0,
                    'description' => 'Minimum service price for Starter tier providers (JMD)',
                    'default' => 1000,
                ],
                'premium_tier_minimum_service_price' => [
                    'label' => 'Premium Tier Minimum Service Price',
                    'type' => 'currency',
                    'min' => 0,
                    'description' => 'Minimum service price for Premium tier providers (JMD)',
                    'default' => 500,
                ],
                'enterprise_tier_minimum_service_price' => [
                    'label' => 'Enterprise Tier Minimum Service Price',
                    'type' => 'currency',
                    'min' => 0,
                    'description' => 'Minimum service price for Enterprise tier (JMD). Set to 0 for no minimum.',
                    'default' => 0,
                ],
            ],
        ],
    ];

    /**
     * Display the settings page.
     */
    public function index(): Response
    {
        $settings = [];

        foreach (self::SETTINGS_SCHEMA as $category => $categoryData) {
            $settings[$category] = [
                'label' => $categoryData['label'],
                'description' => $categoryData['description'],
                'icon' => $categoryData['icon'],
                'settings' => [],
            ];

            foreach ($categoryData['settings'] as $key => $metadata) {
                $value = SystemSetting::get($key, $metadata['default']);

                // Handle different types
                if ($metadata['type'] === 'toggle') {
                    $value = (bool) $value;
                } elseif (is_numeric($value)) {
                    $value = (float) $value;
                }

                $settings[$category]['settings'][$key] = [
                    ...$metadata,
                    'value' => $value,
                    'key' => $key,
                ];
            }
        }

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the system settings.
     */
    public function update(UpdateSystemSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            // Only update settings that exist in our schema
            if ($this->isValidSettingKey($key)) {
                $metadata = $this->getSettingMetadata($key);
                SystemSetting::set($key, $value, $metadata['description'] ?? null);
            }
        }

        // Clear all settings cache to ensure fresh values
        SystemSetting::clearAllCache();

        return back()->with('success', 'System settings updated successfully.');
    }

    /**
     * Check if a setting key is valid.
     */
    private function isValidSettingKey(string $key): bool
    {
        foreach (self::SETTINGS_SCHEMA as $categoryData) {
            if (isset($categoryData['settings'][$key])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get metadata for a setting key.
     */
    private function getSettingMetadata(string $key): ?array
    {
        foreach (self::SETTINGS_SCHEMA as $categoryData) {
            if (isset($categoryData['settings'][$key])) {
                return $categoryData['settings'][$key];
            }
        }

        return null;
    }
}
