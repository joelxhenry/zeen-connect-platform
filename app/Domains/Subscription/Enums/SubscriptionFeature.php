<?php

namespace App\Domains\Subscription\Enums;

enum SubscriptionFeature: string
{
    // Core features (all tiers)
    case DIGITAL_STOREFRONT = 'digital_storefront';
    case EMAIL_NOTIFICATIONS = 'email_notifications';
    case CLIENT_DATABASE = 'client_database';
    case BOOKING_LINK = 'booking_link';

    // Premium features
    case TEAM_MEMBERS = 'team_members';
    case WHATSAPP_NOTIFICATIONS = 'whatsapp_notifications';
    case PRIORITY_LISTING = 'priority_listing';

    // Enterprise features
    case EMBEDDABLE_WIDGETS = 'embeddable_widgets';
    case API_ACCESS = 'api_access';
    case WHITE_LABELING = 'white_labeling';
    case CUSTOM_DEPOSITS = 'custom_deposits';

    /**
     * Get the human-readable label for the feature.
     */
    public function label(): string
    {
        return match ($this) {
            self::DIGITAL_STOREFRONT => 'Digital Storefront',
            self::EMAIL_NOTIFICATIONS => 'Email Notifications',
            self::CLIENT_DATABASE => 'Client Database',
            self::BOOKING_LINK => 'Public Booking Link',
            self::TEAM_MEMBERS => 'Team Members',
            self::WHATSAPP_NOTIFICATIONS => 'WhatsApp Notifications',
            self::PRIORITY_LISTING => 'Priority Directory Listing',
            self::EMBEDDABLE_WIDGETS => 'Embeddable Booking Widgets',
            self::API_ACCESS => 'API Access',
            self::WHITE_LABELING => 'White-Labeling',
            self::CUSTOM_DEPOSITS => 'Custom Deposit Amounts',
        };
    }

    /**
     * Get a description of the feature.
     */
    public function description(): string
    {
        return match ($this) {
            self::DIGITAL_STOREFRONT => 'Your own branded profile page with services, gallery, and reviews',
            self::EMAIL_NOTIFICATIONS => 'Automated email notifications for bookings and reminders',
            self::CLIENT_DATABASE => 'Basic database of your clients and their booking history',
            self::BOOKING_LINK => 'Shareable link for clients to book appointments directly',
            self::TEAM_MEMBERS => 'Add team members to manage your business together',
            self::WHATSAPP_NOTIFICATIONS => 'Send booking notifications via WhatsApp',
            self::PRIORITY_LISTING => 'Get featured placement in the Zeen directory',
            self::EMBEDDABLE_WIDGETS => 'Embed the booking engine directly into your own website',
            self::API_ACCESS => 'Full API access to integrate with external CRMs and apps',
            self::WHITE_LABELING => 'Remove Zeen branding for a fully custom experience',
            self::CUSTOM_DEPOSITS => 'Set custom deposit amounts with no platform minimum',
        };
    }

    /**
     * Get the PrimeVue icon class for the feature.
     */
    public function icon(): string
    {
        return match ($this) {
            self::DIGITAL_STOREFRONT => 'pi pi-home',
            self::EMAIL_NOTIFICATIONS => 'pi pi-envelope',
            self::CLIENT_DATABASE => 'pi pi-users',
            self::BOOKING_LINK => 'pi pi-link',
            self::TEAM_MEMBERS => 'pi pi-user-plus',
            self::WHATSAPP_NOTIFICATIONS => 'pi pi-whatsapp',
            self::PRIORITY_LISTING => 'pi pi-star',
            self::EMBEDDABLE_WIDGETS => 'pi pi-code',
            self::API_ACCESS => 'pi pi-server',
            self::WHITE_LABELING => 'pi pi-palette',
            self::CUSTOM_DEPOSITS => 'pi pi-wallet',
        };
    }

    /**
     * Get the minimum tier required for this feature.
     */
    public function minimumTier(): SubscriptionTier
    {
        return match ($this) {
            // Core features - available on all tiers
            self::DIGITAL_STOREFRONT,
            self::EMAIL_NOTIFICATIONS,
            self::CLIENT_DATABASE,
            self::BOOKING_LINK => SubscriptionTier::FREE,

            // Premium features
            self::TEAM_MEMBERS,
            self::WHATSAPP_NOTIFICATIONS,
            self::PRIORITY_LISTING => SubscriptionTier::PREMIUM,

            // Enterprise features
            self::EMBEDDABLE_WIDGETS,
            self::API_ACCESS,
            self::WHITE_LABELING,
            self::CUSTOM_DEPOSITS => SubscriptionTier::ENTERPRISE,
        };
    }

    /**
     * Get all features as an array.
     *
     * @return array<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Get all features formatted for display.
     *
     * @return array<array{value: string, label: string, description: string, icon: string, minimum_tier: string}>
     */
    public static function allFormatted(): array
    {
        return array_map(fn (self $feature) => [
            'value' => $feature->value,
            'label' => $feature->label(),
            'description' => $feature->description(),
            'icon' => $feature->icon(),
            'minimum_tier' => $feature->minimumTier()->value,
        ], self::cases());
    }
}
