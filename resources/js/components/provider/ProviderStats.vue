<script setup lang="ts">
interface Stats {
    bookings: number;
    rating: number;
    reviewCount: number;
    servicesCount: number;
}

interface Props {
    stats: Stats;
}

defineProps<Props>();

const formatNumber = (num: number): string => {
    if (num >= 1000) {
        return `${(num / 1000).toFixed(1)}k`;
    }
    return num.toString();
};
</script>

<template>
    <section class="provider-stats">
        <div class="provider-stats__container">
            <div class="provider-stats__grid">
                <div class="provider-stats__card">
                    <i class="pi pi-calendar-plus provider-stats__icon"></i>
                    <span class="provider-stats__value">{{ formatNumber(stats.bookings) }}+</span>
                    <span class="provider-stats__label">Bookings</span>
                </div>
                <div class="provider-stats__card">
                    <i class="pi pi-star-fill provider-stats__icon provider-stats__icon--gold"></i>
                    <span class="provider-stats__value">{{ stats.rating }}</span>
                    <span class="provider-stats__label">Rating</span>
                </div>
                <div class="provider-stats__card">
                    <i class="pi pi-comments provider-stats__icon"></i>
                    <span class="provider-stats__value">{{ formatNumber(stats.reviewCount) }}</span>
                    <span class="provider-stats__label">Reviews</span>
                </div>
                <div class="provider-stats__card">
                    <i class="pi pi-list provider-stats__icon"></i>
                    <span class="provider-stats__value">{{ stats.servicesCount }}</span>
                    <span class="provider-stats__label">Services</span>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.provider-stats {
    background: #f9fafb;
    padding: 1.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.provider-stats__container {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

.provider-stats__grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (min-width: 768px) {
    .provider-stats__grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.provider-stats__card {
    background: white;
    padding: 1.25rem;
    border-radius: 0.75rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 0.25rem;
}

.provider-stats__icon {
    font-size: 1.5rem;
    color: var(--provider-primary);
    margin-bottom: 0.25rem;
}

.provider-stats__icon--gold {
    color: #F59E0B;
}

.provider-stats__value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--provider-text);
}

.provider-stats__label {
    font-size: 0.8125rem;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}
</style>
