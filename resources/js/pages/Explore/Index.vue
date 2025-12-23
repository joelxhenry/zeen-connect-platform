<script setup lang="ts">
import { computed } from 'vue';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import ProviderCard from '@/components/explore/ProviderCard.vue';
import FilterSidebar from '@/components/explore/FilterSidebar.vue';
import Paginator from 'primevue/paginator';
import { router } from '@inertiajs/vue3';

interface ProviderCardData {
    id: number;
    uuid: string;
    slug: string;
    business_name: string;
    tagline?: string;
    avatar?: string;
    location?: string;
    rating_avg: number;
    rating_count: number;
    services_count: number;
    is_featured: boolean;
    categories: Array<{ id: number; name: string; icon?: string }>;
    preview_services: Array<{ id: number; name: string; price: number; duration_display: string }>;
}

interface PaginatedProviders {
    data: ProviderCardData[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
}

interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon?: string;
}

interface Region {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    locations: Array<{ id: number; name: string; slug: string }>;
}

interface Filters {
    search?: string;
    category?: number;
    region?: number;
    location?: number;
    sort?: string;
}

const props = defineProps<{
    providers: PaginatedProviders;
    categories: Category[];
    regions: Region[];
    filters: Filters;
}>();

const onPageChange = (event: { page: number }) => {
    const params: Record<string, unknown> = { ...props.filters, page: event.page + 1 };
    router.get(route('explore'), params, {
        preserveState: true,
        preserveScroll: true,
    });
};

const resultText = computed(() => {
    if (props.providers.total === 0) return 'No providers found';
    if (props.providers.total === 1) return '1 provider found';
    return `${props.providers.total} providers found`;
});

const hasFilters = computed(() => {
    return props.filters.search || props.filters.category || props.filters.region || props.filters.location;
});
</script>

<template>
    <PublicLayout title="Explore Providers">
        <div class="explore-page">
            <div class="explore-header">
                <div class="header-content">
                    <h1 class="page-title">Explore Providers</h1>
                    <p class="page-subtitle">Discover talented service providers in Jamaica</p>
                </div>
            </div>

            <div class="explore-container">
                <FilterSidebar
                    :categories="categories"
                    :regions="regions"
                    :filters="filters"
                    class="sidebar"
                />

                <div class="main-content">
                    <div class="results-header">
                        <span class="results-count">{{ resultText }}</span>
                    </div>

                    <div v-if="providers.data.length > 0" class="providers-grid">
                        <ProviderCard
                            v-for="provider in providers.data"
                            :key="provider.id"
                            :provider="provider"
                        />
                    </div>

                    <div v-else class="empty-state">
                        <div class="empty-icon">
                            <i class="pi pi-search"></i>
                        </div>
                        <h3 class="empty-title">No providers found</h3>
                        <p class="empty-text" v-if="hasFilters">
                            Try adjusting your filters or search terms to find what you're looking for.
                        </p>
                        <p class="empty-text" v-else>
                            There are no providers available at the moment. Check back soon!
                        </p>
                    </div>

                    <div v-if="providers.last_page > 1" class="pagination-wrapper">
                        <Paginator
                            :rows="providers.per_page"
                            :totalRecords="providers.total"
                            :first="(providers.current_page - 1) * providers.per_page"
                            @page="onPageChange"
                            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                        />
                    </div>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.explore-page {
    min-height: calc(100vh - 64px);
}

.explore-header {
    background: linear-gradient(135deg, var(--p-primary-50) 0%, var(--p-primary-100) 100%);
    padding: 2.5rem 1.5rem;
    border-bottom: 1px solid var(--p-surface-200);
}

.header-content {
    max-width: 1280px;
    margin: 0 auto;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0 0 0.5rem 0;
}

.page-subtitle {
    font-size: 1.0625rem;
    color: var(--p-surface-600);
    margin: 0;
}

.explore-container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 1.5rem;
    display: grid;
    grid-template-columns: 280px 1fr;
    gap: 1.5rem;
}

@media (max-width: 1024px) {
    .explore-container {
        grid-template-columns: 1fr;
    }

    .sidebar {
        position: static;
    }
}

.main-content {
    min-width: 0;
}

.results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
}

.results-count {
    font-size: 0.9375rem;
    color: var(--p-surface-600);
}

.providers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 1.25rem;
}

@media (max-width: 768px) {
    .providers-grid {
        grid-template-columns: 1fr;
    }
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 16px;
    text-align: center;
}

.empty-icon {
    width: 64px;
    height: 64px;
    background-color: var(--p-surface-100);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
}

.empty-icon i {
    font-size: 1.5rem;
    color: var(--p-surface-400);
}

.empty-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.5rem 0;
}

.empty-text {
    font-size: 0.9375rem;
    color: var(--p-surface-500);
    margin: 0;
    max-width: 400px;
}

.pagination-wrapper {
    margin-top: 2rem;
    display: flex;
    justify-content: center;
}
</style>
