<script setup lang="ts">
interface ServiceHighlight {
    id: number;
    name: string;
    icon?: string;
    image?: string;
}

interface Props {
    services: ServiceHighlight[];
    maxDisplay?: number;
    variant?: 'light' | 'dark';
}

const props = withDefaults(defineProps<Props>(), {
    maxDisplay: 5,
    variant: 'light',
});

const displayedServices = props.services.slice(0, props.maxDisplay);

const getBookingUrl = (serviceId: number) => `/book?service=${serviceId}`;
</script>

<template>
    <div class="service-highlights" :class="`service-highlights--${variant}`">
        <AppLink
            v-for="service in displayedServices"
            :key="service.id"
            :href="getBookingUrl(service.id)"
            class="service-highlight"
        >
            <div v-if="service.image" class="service-highlight__image">
                <img :src="service.image" :alt="service.name" />
            </div>
            <i
                v-else-if="service.icon"
                :class="service.icon"
                class="service-highlight__icon"
            ></i>
            <span class="service-highlight__name">{{ service.name }}</span>
        </AppLink>
    </div>
</template>

<style scoped>
.service-highlights {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: center;
}

.service-highlight {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    text-decoration: none;
    transition: all 0.2s;
    font-size: 0.875rem;
    font-weight: 500;
}

.service-highlights--light .service-highlight {
    background: #fff;
    border: 1px solid #e5e7eb;
    color: var(--provider-text, #1f2937);
}

.service-highlights--light .service-highlight:hover {
    border-color: var(--provider-primary, #C4A962);
    background: var(--provider-primary-10, rgba(196, 169, 98, 0.1));
}

.service-highlights--dark .service-highlight {
    background: var(--barber-dark-secondary, #3D3024);
    border: 1px solid var(--barber-dark-secondary, #3D3024);
    color: #fff;
}

.service-highlights--dark .service-highlight:hover {
    border-color: var(--provider-primary, #C4A962);
    background: var(--provider-primary, #C4A962);
    color: var(--barber-dark, #2A2018);
}

.service-highlight__image {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    overflow: hidden;
}

.service-highlight__image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.service-highlight__icon {
    font-size: 1rem;
    color: var(--provider-primary, #C4A962);
}

.service-highlight__name {
    white-space: nowrap;
}
</style>
