<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import Button from 'primevue/button';

defineProps<{
    currentTier: string;
    currentTierLabel: string;
}>();
</script>

<template>
    <ConsoleLayout title="Team Members">
        <div class="upgrade-page">
            <div class="upgrade-card">
                <div class="upgrade-icon">
                    <i class="pi pi-users"></i>
                </div>
                <h2 class="upgrade-title">Team Members</h2>
                <p class="upgrade-description">
                    Add team members to help manage your business. Assign permissions so they can handle
                    bookings, services, and more - while you stay in control.
                </p>
                <div class="upgrade-features">
                    <div class="feature-item">
                        <i class="pi pi-check-circle"></i>
                        <span>Invite team members via email</span>
                    </div>
                    <div class="feature-item">
                        <i class="pi pi-check-circle"></i>
                        <span>Assign granular permissions</span>
                    </div>
                    <div class="feature-item">
                        <i class="pi pi-check-circle"></i>
                        <span>Track who makes changes</span>
                    </div>
                    <div class="feature-item">
                        <i class="pi pi-check-circle"></i>
                        <span>Suspend or remove access anytime</span>
                    </div>
                </div>
                <div class="upgrade-cta">
                    <p class="cta-text">
                        Team members are available on <strong>Premium</strong> and <strong>Enterprise</strong> plans.
                        You're currently on the <strong>{{ currentTierLabel }}</strong> plan.
                    </p>
                    <Button
                        label="Upgrade Your Plan"
                        icon="pi pi-arrow-up-right"
                        size="large"
                        @click="router.visit(resolveUrl(provider.subscription.index.url()))"
                    />
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.upgrade-page {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    padding: 2rem;
}

.upgrade-card {
    max-width: 500px;
    padding: 2.5rem;
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 1rem;
    text-align: center;
}

.upgrade-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    margin-bottom: 1.5rem;
    background: rgba(16, 107, 79, 0.1);
    border-radius: 1rem;
}

.upgrade-icon i {
    font-size: 1.75rem;
    color: #106B4F;
}

.upgrade-title {
    margin: 0 0 0.75rem;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--color-slate-900, #0f172a);
}

.upgrade-description {
    margin: 0 0 1.5rem;
    font-size: 0.9375rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.6;
}

.upgrade-features {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 2rem;
    text-align: left;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
    color: var(--color-slate-700, #334155);
}

.feature-item i {
    color: #22c55e;
    font-size: 1rem;
}

.upgrade-cta {
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-slate-100, #f1f5f9);
}

.cta-text {
    margin: 0 0 1.25rem;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.5;
}

.cta-text strong {
    color: var(--color-slate-700, #334155);
}
</style>
