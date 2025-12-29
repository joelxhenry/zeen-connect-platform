<script setup lang="ts">
interface ServiceHighlight {
    id: number;
    name: string;
    icon?: string;
}

interface Props {
    services: ServiceHighlight[];
    maxDisplay?: number;
}

const props = withDefaults(defineProps<Props>(), {
    maxDisplay: 5,
});

const displayedServices = props.services.slice(0, props.maxDisplay);

const getBookingUrl = (serviceId: number) => `/book?service=${serviceId}`;
</script>

<template>
    <div class="service-pills">
        <AppLink
            v-for="service in displayedServices"
            :key="service.id"
            :href="getBookingUrl(service.id)"
            class="service-pill"
        >
            <i
                v-if="service.icon"
                :class="service.icon"
                class="service-pill__icon"
            ></i>
            <span class="service-pill__name">{{ service.name }}</span>
        </AppLink>
    </div>
</template>

<style scoped>
.service-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: center;
}

.service-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 9999px;
    background: var(--provider-surface, #fff);
    border: 1px solid var(--provider-border, #e5e7eb);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--provider-text, #1f2937);
    transition: all 0.2s;
}

.service-pill:hover {
    border-color: var(--provider-primary, #3b82f6);
    background: var(--provider-primary-10, rgba(59, 130, 246, 0.1));
    color: var(--provider-primary, #3b82f6);
}

.service-pill__icon {
    font-size: 1rem;
    color: var(--provider-primary, #3b82f6);
}

.service-pill__name {
    white-space: nowrap;
}

@media (max-width: 640px) {
    .service-pills {
        gap: 0.5rem;
    }

    .service-pill {
        padding: 0.5rem 1rem;
        font-size: 0.8125rem;
    }
}
</style>
