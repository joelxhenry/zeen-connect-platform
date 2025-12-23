<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/components/layout/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

interface FavoriteProvider {
    id: number;
    uuid: string;
    slug: string;
    business_name: string;
    tagline: string | null;
    avatar: string | null;
    location: string | null;
    rating_avg: number;
    rating_count: number;
    services_count: number;
    is_featured: boolean;
}

const props = defineProps<{
    favorites: FavoriteProvider[];
}>();

const confirm = useConfirm();
const removing = ref<string | null>(null);

const removeFavorite = (provider: FavoriteProvider) => {
    confirm.require({
        message: `Remove ${provider.business_name} from your favorites?`,
        header: 'Remove Favorite',
        icon: 'pi pi-heart',
        acceptClass: 'p-button-danger',
        accept: () => {
            removing.value = provider.slug;
            router.delete(route('client.favorites.destroy', provider.slug), {
                preserveScroll: true,
                onFinish: () => {
                    removing.value = null;
                },
            });
        },
    });
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <DashboardLayout title="Favorite Providers">
        <Head title="My Favorites" />

        <ConfirmDialog />

        <div class="favorites-page">
            <div class="page-header">
                <div class="header-content">
                    <h1 class="page-title">
                        <i class="pi pi-heart-fill"></i>
                        My Favorites
                    </h1>
                    <p class="page-subtitle">Providers you've saved for quick access</p>
                </div>
                <Link :href="route('explore')" class="explore-link">
                    <Button
                        label="Find More Providers"
                        icon="pi pi-search"
                        outlined
                    />
                </Link>
            </div>

            <div v-if="favorites.length === 0" class="empty-state">
                <div class="empty-icon">
                    <i class="pi pi-heart"></i>
                </div>
                <h3>No favorites yet</h3>
                <p>Browse providers and tap the heart icon to save them here for quick access.</p>
                <Link :href="route('explore')">
                    <Button label="Explore Providers" icon="pi pi-search" />
                </Link>
            </div>

            <div v-else class="favorites-grid">
                <Card
                    v-for="provider in favorites"
                    :key="provider.id"
                    class="favorite-card"
                >
                    <template #content>
                        <div class="provider-content">
                            <Link :href="route('provider.public', provider.slug)" class="provider-link">
                                <div class="provider-avatar">
                                    <img
                                        v-if="provider.avatar"
                                        :src="provider.avatar"
                                        :alt="provider.business_name"
                                    />
                                    <div v-else class="avatar-placeholder">
                                        {{ getInitials(provider.business_name) }}
                                    </div>
                                    <Tag
                                        v-if="provider.is_featured"
                                        value="Featured"
                                        severity="warning"
                                        class="featured-badge"
                                    />
                                </div>

                                <div class="provider-info">
                                    <h3 class="provider-name">{{ provider.business_name }}</h3>
                                    <p v-if="provider.tagline" class="provider-tagline">{{ provider.tagline }}</p>

                                    <div class="provider-meta">
                                        <span v-if="provider.location" class="meta-item">
                                            <i class="pi pi-map-marker"></i>
                                            {{ provider.location }}
                                        </span>
                                        <span class="meta-item">
                                            <i class="pi pi-briefcase"></i>
                                            {{ provider.services_count }} services
                                        </span>
                                    </div>

                                    <div class="provider-rating">
                                        <div v-if="provider.rating_count > 0" class="rating">
                                            <i class="pi pi-star-fill"></i>
                                            <span class="rating-value">{{ provider.rating_avg.toFixed(1) }}</span>
                                            <span class="rating-count">({{ provider.rating_count }})</span>
                                        </div>
                                        <span v-else class="no-reviews">New provider</span>
                                    </div>
                                </div>
                            </Link>

                            <div class="provider-actions">
                                <Link :href="route('booking.create', { provider: provider.slug })">
                                    <Button
                                        label="Book Now"
                                        icon="pi pi-calendar"
                                        size="small"
                                    />
                                </Link>
                                <Button
                                    icon="pi pi-heart-fill"
                                    severity="danger"
                                    text
                                    rounded
                                    :loading="removing === provider.slug"
                                    @click="removeFavorite(provider)"
                                    v-tooltip.top="'Remove from favorites'"
                                />
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.favorites-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.header-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.page-title i {
    color: var(--p-red-500);
}

.page-subtitle {
    color: var(--p-surface-500);
    margin: 0;
}

.explore-link {
    text-decoration: none;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 4rem 2rem;
    text-align: center;
    background: var(--p-surface-0);
    border-radius: 12px;
    border: 1px dashed var(--p-surface-300);
}

.empty-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: var(--p-red-50);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.empty-icon i {
    font-size: 2rem;
    color: var(--p-red-400);
}

.empty-state h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.5rem 0;
}

.empty-state p {
    color: var(--p-surface-500);
    max-width: 300px;
    margin: 0 0 1.5rem 0;
}

.favorites-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1rem;
}

.favorite-card {
    border-radius: 12px;
    overflow: hidden;
    transition: box-shadow 0.2s;
}

.favorite-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.provider-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.provider-link {
    display: flex;
    gap: 1rem;
    text-decoration: none;
    color: inherit;
}

.provider-avatar {
    position: relative;
    flex-shrink: 0;
}

.provider-avatar img {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    object-fit: cover;
}

.avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--p-primary-color), var(--p-primary-400));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.25rem;
}

.featured-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    font-size: 0.625rem;
}

.provider-info {
    flex: 1;
    min-width: 0;
}

.provider-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.provider-tagline {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
    margin: 0.25rem 0 0 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.provider-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.meta-item i {
    font-size: 0.75rem;
}

.provider-rating {
    margin-top: 0.5rem;
}

.rating {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.rating i {
    color: var(--p-yellow-500);
    font-size: 0.875rem;
}

.rating-value {
    font-weight: 600;
    color: var(--p-surface-900);
}

.rating-count {
    color: var(--p-surface-400);
    font-size: 0.8125rem;
}

.no-reviews {
    font-size: 0.8125rem;
    color: var(--p-surface-400);
}

.provider-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 1rem;
    border-top: 1px solid var(--p-surface-200);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
    }

    .favorites-grid {
        grid-template-columns: 1fr;
    }

    .provider-link {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .provider-meta {
        justify-content: center;
    }
}
</style>
