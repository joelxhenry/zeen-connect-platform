<script setup lang="ts">
withDefaults(defineProps<{
    columns?: 2 | 3 | 4;
    gap?: 'sm' | 'md' | 'lg';
    title?: string;
    subtitle?: string;
}>(), {
    columns: 3,
    gap: 'md',
});
</script>

<template>
    <section class="bento-section">
        <div v-if="title || subtitle" class="section-header">
            <h2 v-if="title">{{ title }}</h2>
            <p v-if="subtitle">{{ subtitle }}</p>
        </div>

        <div
            class="bento-grid"
            :class="[
                `cols-${columns}`,
                `gap-${gap}`
            ]"
        >
            <slot />
        </div>
    </section>
</template>

<style scoped>
.bento-section {
    padding: 4rem 0;
}

.section-header {
    text-align: center;
    margin-bottom: 3rem;
}

.section-header h2 {
    margin: 0 0 0.75rem 0;
    font-size: 2rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.section-header p {
    margin: 0;
    font-size: 1rem;
    color: var(--provider-secondary, #6b7280);
    max-width: 600px;
    margin: 0 auto;
}

.bento-grid {
    display: grid;
}

/* Column variations */
.bento-grid.cols-2 {
    grid-template-columns: repeat(2, 1fr);
}

.bento-grid.cols-3 {
    grid-template-columns: repeat(3, 1fr);
}

.bento-grid.cols-4 {
    grid-template-columns: repeat(4, 1fr);
}

/* Gap variations */
.bento-grid.gap-sm {
    gap: 1px;
    background: var(--provider-border, #e5e5e5);
    border: 1px solid var(--provider-border, #e5e5e5);
}

.bento-grid.gap-md {
    gap: 1.5rem;
}

.bento-grid.gap-lg {
    gap: 2rem;
}

/* Responsive */
@media (max-width: 1024px) {
    .bento-grid.cols-4 {
        grid-template-columns: repeat(2, 1fr);
    }

    .bento-grid.cols-3 {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .bento-section {
        padding: 3rem 0;
    }

    .section-header h2 {
        font-size: 1.5rem;
    }

    .bento-grid.cols-2,
    .bento-grid.cols-3,
    .bento-grid.cols-4 {
        grid-template-columns: 1fr;
    }

    .bento-grid.gap-md {
        gap: 1rem;
    }

    .bento-grid.gap-lg {
        gap: 1.5rem;
    }
}
</style>
