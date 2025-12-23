<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    modelValue?: number;
    readonly?: boolean;
    size?: 'small' | 'medium' | 'large';
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: number): void;
}>();

const starSize = computed(() => {
    switch (props.size) {
        case 'small': return '1rem';
        case 'large': return '2rem';
        default: return '1.5rem';
    }
});

const setRating = (rating: number) => {
    if (!props.readonly) {
        emit('update:modelValue', rating);
    }
};
</script>

<template>
    <div class="star-rating" :class="{ readonly, interactive: !readonly }">
        <button
            v-for="star in 5"
            :key="star"
            type="button"
            class="star-button"
            :class="{ filled: star <= (modelValue || 0) }"
            :disabled="readonly"
            @click="setRating(star)"
        >
            <i class="pi" :class="star <= (modelValue || 0) ? 'pi-star-fill' : 'pi-star'"></i>
        </button>
    </div>
</template>

<style scoped>
.star-rating {
    display: inline-flex;
    gap: 0.125rem;
}

.star-button {
    background: none;
    border: none;
    padding: 0.125rem;
    cursor: pointer;
    transition: transform 0.1s;
    font-size: v-bind(starSize);
    line-height: 1;
}

.star-button i {
    color: var(--p-surface-300);
    transition: color 0.15s;
}

.star-button.filled i {
    color: #fbbf24;
}

.star-rating.interactive .star-button:hover {
    transform: scale(1.1);
}

.star-rating.interactive .star-button:hover i,
.star-rating.interactive .star-button:hover ~ .star-button i {
    color: #fbbf24;
}

.star-rating.readonly .star-button {
    cursor: default;
    pointer-events: none;
}
</style>
