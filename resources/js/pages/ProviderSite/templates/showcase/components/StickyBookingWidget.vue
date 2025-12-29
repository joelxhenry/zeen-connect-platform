<script setup lang="ts">
import Button from 'primevue/button';

defineProps<{
    bookingUrl: string;
    startingPrice?: string;
    serviceName?: string;
}>();
</script>

<template>
    <!-- Desktop: Floating Sidebar -->
    <div class="sticky-booking-widget">
        <div class="widget-card">
            <span class="widget-label">Book Your Experience</span>
            <div v-if="startingPrice" class="widget-price">
                <span class="price-label">Starting from</span>
                <span class="price-value">{{ startingPrice }}</span>
            </div>
            <div v-if="serviceName" class="widget-service">
                {{ serviceName }}
            </div>
            <AppLink :href="bookingUrl" class="widget-link">
                <Button label="BOOK NOW" class="book-btn" />
            </AppLink>
        </div>
    </div>

    <!-- Mobile: Fixed Footer Bar -->
    <div class="mobile-booking-bar">
        <div class="mobile-bar-content">
            <div class="mobile-bar-info">
                <span class="mobile-bar-label">{{ serviceName || 'Book Your Experience' }}</span>
                <span v-if="startingPrice" class="mobile-bar-price">{{ startingPrice }}</span>
            </div>
            <AppLink :href="bookingUrl" class="mobile-bar-link">
                <Button label="BOOK NOW" class="book-btn book-btn--mobile" />
            </AppLink>
        </div>
    </div>
</template>

<style scoped>
/* Desktop: Floating Sidebar Widget */
.sticky-booking-widget {
    display: none;
}

@media (min-width: 1024px) {
    .sticky-booking-widget {
        display: block;
        position: fixed;
        top: 50%;
        right: 2rem;
        transform: translateY(-50%);
        z-index: 40;
        width: 280px;
    }
}

.widget-card {
    background: var(--provider-surface, #fff);
    border: 2px solid var(--provider-text, #1a1a1a);
    padding: 1.5rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
}

.widget-label {
    display: block;
    font-family: var(--font-heading, 'Oswald', sans-serif);
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--provider-secondary, #6b7280);
    margin-bottom: 0.75rem;
}

.widget-price {
    margin-bottom: 1rem;
}

.price-label {
    display: block;
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
    margin-bottom: 0.25rem;
}

.price-value {
    display: block;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    letter-spacing: 0.02em;
}

.widget-service {
    font-size: 0.875rem;
    color: var(--provider-secondary, #6b7280);
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--provider-border, #e5e5e5);
}

.widget-link {
    display: block;
    text-decoration: none;
}

/* Book Now button */
:deep(.book-btn) {
    width: 100%;
    font-family: var(--font-mono, 'Space Mono', monospace) !important;
    font-weight: 700;
    font-size: 0.75rem;
    letter-spacing: 0.1em;
    background-color: var(--provider-primary, #1a1a1a) !important;
    border-color: var(--provider-primary, #1a1a1a) !important;
    border-radius: 0 !important;
    padding: 1rem 1.5rem;
}

:deep(.book-btn:hover) {
    background-color: var(--provider-primary-hover, #333) !important;
    border-color: var(--provider-primary-hover, #333) !important;
}

/* Mobile: Fixed Footer Bar */
.mobile-booking-bar {
    display: block;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 50;
    background: var(--provider-surface, #fff);
    border-top: 2px solid var(--provider-text, #1a1a1a);
    padding: 1rem 1.5rem;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
}

@media (min-width: 1024px) {
    .mobile-booking-bar {
        display: none;
    }
}

.mobile-bar-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    max-width: 600px;
    margin: 0 auto;
    gap: 1rem;
}

.mobile-bar-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
    flex: 1;
}

.mobile-bar-label {
    font-size: 0.75rem;
    color: var(--provider-secondary, #6b7280);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mobile-bar-price {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
}

.mobile-bar-link {
    text-decoration: none;
    flex-shrink: 0;
}

:deep(.book-btn--mobile) {
    padding: 0.75rem 1.25rem;
}

/* Ensure content doesn't hide behind mobile bar */
@media (max-width: 1023px) {
    :global(.showcase-layout .main-content) {
        padding-bottom: 80px;
    }
}
</style>
