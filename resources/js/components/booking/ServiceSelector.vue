<script setup lang="ts">
import Tag from 'primevue/tag';
import type { ServiceForBooking } from '@/types/models/service';

interface Props {
    services: ServiceForBooking[];
    modelValue: ServiceForBooking | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    'update:modelValue': [value: ServiceForBooking | null];
}>();

const selectService = (service: ServiceForBooking) => {
    emit('update:modelValue', service);
};

const isSelected = (service: ServiceForBooking) => {
    return props.modelValue?.id === service.id;
};
</script>

<template>
    <div class="service-selector">
        <div v-for="service in services" :key="service.id" class="service-card"
            :class="{ 'service-card--selected': isSelected(service) }" @click="selectService(service)">
            <div class="service-card__content">
                <div class="service-card__info">
                    <h3 class="service-card__name">{{ service.name }}</h3>
                    <p v-if="service.description" class="service-card__description">
                        {{ service.description }}
                    </p>
                    <div class="service-card__meta">
                        <span class="service-card__duration">
                            <i class="pi pi-clock"></i>
                            {{ service.duration_display }}
                        </span>
                        <Tag v-if="service.category" :value="service.category.name" severity="secondary" />
                    </div>
                </div>
                <span class="service-card__price">{{ service.price_display }}</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.service-selector {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.service-card {
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s;
}

.service-card:hover {
    border-color: #d1d5db;
}

.service-card--selected {
    border-color: #106B4F;
    background-color: rgba(16, 107, 79, 0.05);
}

.service-card__content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
}

.service-card__info {
    flex: 1;
    min-width: 0;
}

.service-card__name {
    margin: 0;
    font-size: 1rem;
    font-weight: 500;
    color: #0D1F1B;
}

.service-card__description {
    margin: 0.25rem 0 0 0;
    font-size: 0.875rem;
    color: #6b7280;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.service-card__meta {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.service-card__duration {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.service-card__price {
    font-size: 1.125rem;
    font-weight: 600;
    color: #106B4F;
    white-space: nowrap;
}
</style>
