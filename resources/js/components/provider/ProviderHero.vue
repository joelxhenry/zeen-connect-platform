<script setup lang="ts">
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Rating from 'primevue/rating';
import Tag from 'primevue/tag';

interface Provider {
    business_name: string;
    tagline?: string;
    avatar?: string;
    cover_image?: string;
    verified_at?: string;
    rating_avg: number;
    rating_count: number;
    rating_display: string;
    location?: string;
}

interface Props {
    provider: Provider;
    bookingUrl: string;
}

defineProps<Props>();

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <section class="provider-hero">
        <!-- Cover Image -->
        <div
            class="provider-hero__cover"
            :style="provider.cover_image
                ? { backgroundImage: `url(${provider.cover_image})` }
                : null"
        >
            <div class="provider-hero__cover-overlay"></div>
        </div>

        <!-- Profile Card -->
        <div class="provider-hero__card-wrapper">
            <div class="provider-hero__card">
                <!-- Avatar (overlaps cover) -->
                <div class="provider-hero__avatar-wrapper">
                    <Avatar
                        v-if="provider.avatar"
                        :image="provider.avatar"
                        shape="circle"
                        class="provider-hero__avatar"
                    />
                    <Avatar
                        v-else
                        :label="getInitials(provider.business_name)"
                        shape="circle"
                        class="provider-hero__avatar provider-hero__avatar--initials"
                    />
                </div>

                <!-- Info -->
                <div class="provider-hero__info">
                    <div class="provider-hero__name-row">
                        <h1>{{ provider.business_name }}</h1>
                        <Tag
                            v-if="provider.verified_at"
                            value="Verified"
                            severity="success"
                            class="!text-xs"
                        />
                    </div>
                    <p v-if="provider.tagline" class="provider-hero__tagline">
                        {{ provider.tagline }}
                    </p>
                    <div class="provider-hero__meta">
                        <div v-if="provider.rating_count > 0" class="provider-hero__rating">
                            <Rating :modelValue="provider.rating_avg" readonly :cancel="false" />
                            <span>{{ provider.rating_display }} ({{ provider.rating_count }} reviews)</span>
                        </div>
                        <span v-if="provider.location" class="provider-hero__location">
                            <i class="pi pi-map-marker"></i>
                            {{ provider.location }}
                        </span>
                    </div>
                </div>

                <!-- CTA -->
                <div class="provider-hero__actions">
                    <AppLink :href="bookingUrl">
                        <Button
                            label="Book Now"
                            icon="pi pi-calendar"
                            class="!bg-[#106B4F] !border-[#106B4F] !px-6"
                        />
                    </AppLink>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.provider-hero {
    position: relative;
    background: #f9fafb;
}

.provider-hero__cover {
    height: 180px;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 50%, #d1d5db 100%);
    background-size: cover;
    background-position: center;
    position: relative;
}

@media (min-width: 768px) {
    .provider-hero__cover {
        height: 220px;
    }
}

.provider-hero__cover-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to bottom, transparent 50%, rgba(0, 0, 0, 0.1) 100%);
}

.provider-hero__card-wrapper {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    margin-top: -3rem;
    position: relative;
    z-index: 10;
}

.provider-hero__card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    flex-wrap: wrap;
}

.provider-hero__avatar-wrapper {
    flex-shrink: 0;
    margin-top: -3.5rem;
}

.provider-hero__avatar {
    width: 6rem !important;
    height: 6rem !important;
    border: 4px solid white;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

@media (min-width: 768px) {
    .provider-hero__avatar {
        width: 7rem !important;
        height: 7rem !important;
    }

    .provider-hero__avatar-wrapper {
        margin-top: -4rem;
    }
}

.provider-hero__avatar--initials {
    background-color: #106B4F !important;
    font-size: 2rem !important;
    color: white !important;
}

.provider-hero__info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.provider-hero__name-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.provider-hero__name-row h1 {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: #0D1F1B;
}

@media (min-width: 768px) {
    .provider-hero__name-row h1 {
        font-size: 1.75rem;
    }
}

.provider-hero__tagline {
    margin: 0;
    color: #6b7280;
    font-size: 0.9375rem;
}

.provider-hero__meta {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-top: 0.25rem;
    flex-wrap: wrap;
}

.provider-hero__rating {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.provider-hero__location {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.provider-hero__location i {
    color: #106B4F;
}

.provider-hero__actions {
    display: flex;
    gap: 1rem;
    flex-shrink: 0;
}

@media (max-width: 640px) {
    .provider-hero__card {
        flex-direction: column;
        text-align: center;
        padding-top: 1rem;
    }

    .provider-hero__avatar-wrapper {
        margin-top: -4rem;
    }

    .provider-hero__info {
        align-items: center;
    }

    .provider-hero__name-row {
        justify-content: center;
    }

    .provider-hero__meta {
        justify-content: center;
    }

    .provider-hero__actions {
        width: 100%;
    }

    .provider-hero__actions :deep(.p-button) {
        width: 100%;
    }
}
</style>
