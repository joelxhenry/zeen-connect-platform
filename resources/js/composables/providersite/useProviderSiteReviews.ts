import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { ReviewsPageProps } from '@/types/providersite';

/**
 * Composable for provider site reviews page logic.
 *
 * Handles pagination and rating distribution calculations.
 */
export function useProviderSiteReviews(props: ReviewsPageProps) {
    /**
     * Calculate the percentage of reviews for a specific star rating.
     */
    const getDistributionPercentage = (count: number): number => {
        if (props.reviewStats.total === 0) return 0;
        return Math.round((count / props.reviewStats.total) * 100);
    };

    /**
     * Check if there are more pages of reviews to load.
     */
    const hasMorePages = computed(() =>
        props.reviews.current_page < props.reviews.last_page
    );

    /**
     * Load the next page of reviews using Inertia.
     */
    const loadMore = () => {
        if (hasMorePages.value) {
            router.get('/reviews', { page: props.reviews.current_page + 1 }, {
                preserveState: true,
                preserveScroll: true,
            });
        }
    };

    return {
        getDistributionPercentage,
        hasMorePages,
        loadMore,
    };
}
