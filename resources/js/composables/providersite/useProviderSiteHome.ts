import { computed } from 'vue';
import type {
    HomePageProps,
    UseProviderSiteHomeReturn,
} from '@/types/providersite';

/**
 * Composable for provider site home page logic.
 *
 * Extracts all business logic from the home page component so it can be
 * reused across different templates.
 */
export function useProviderSiteHome(props: HomePageProps): UseProviderSiteHomeReturn {
    // Computed stats for display
    const stats = computed(() => ({
        bookings: props.provider.total_bookings,
        rating: props.provider.rating_avg,
        reviewCount: props.provider.rating_count,
        servicesCount: props.provider.services_count,
    }));

    // URL generators (relative paths since we're on the provider's subdomain)
    const bookingUrl = '/book';
    const servicesUrl = '/services';
    const reviewsUrl = '/reviews';

    const getServiceBookingUrl = (serviceId: number) => `/book?service=${serviceId}`;

    // Computed checks for conditional rendering
    const hasPortfolio = computed(() =>
        (props.provider.gallery?.length ?? 0) > 0 ||
        (props.provider.videos?.length ?? 0) > 0
    );

    const hasServices = computed(() => props.servicesByCategory.length > 0);
    const hasAvailability = computed(() => props.availability.length > 0);
    const hasReviews = computed(() => props.reviews.length > 0);

    return {
        stats: stats.value,
        bookingUrl,
        servicesUrl,
        reviewsUrl,
        getServiceBookingUrl,
        hasPortfolio: hasPortfolio.value,
        hasServices: hasServices.value,
        hasAvailability: hasAvailability.value,
        hasReviews: hasReviews.value,
    };
}
