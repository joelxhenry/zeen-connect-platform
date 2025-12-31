<script setup lang="ts">
import SettingsLayout from '@/components/layout/SettingsLayout.vue';
import SettingsCard from '@/components/settings/SettingsCard.vue';
import provider from '@/routes/provider';

defineProps<{
    canManageTeam: boolean;
}>();
</script>

<template>
    <SettingsLayout title="Settings">
        <div class="settings-page">
            <p class="page-subtitle">
                Manage your profile, branding, team, and booking preferences
            </p>

            <div class="settings-grid">
                <!-- My Brand -->
                <SettingsCard
                    icon="pi pi-palette"
                    title="My Brand"
                    description="Logo, colors, cover photo, and site template"
                    :href="provider.branding.edit.url()"
                />

                <!-- Your Profile -->
                <SettingsCard
                    icon="pi pi-user"
                    title="Your Profile"
                    description="Personal info, role, and availability"
                    :href="provider.profile.edit.url()"
                />

                <!-- Teams (Tier-dependent) -->
                <SettingsCard
                    v-if="canManageTeam"
                    icon="pi pi-users"
                    title="Teams"
                    description="Manage team members and their schedules"
                    :href="provider.team.index.url()"
                />

                <!-- Booking Preferences -->
                <SettingsCard
                    icon="pi pi-calendar-clock"
                    title="Booking Preferences"
                    description="Lead times, deposits, and cancellation policies"
                    :href="provider.settings.edit.url()"
                />

                <!-- Availability -->
                <SettingsCard
                    icon="pi pi-clock"
                    title="Availability"
                    description="Working hours, breaks, and time off"
                    :href="provider.availability.edit.url()"
                />

                <!-- Subscription -->
                <SettingsCard
                    icon="pi pi-credit-card"
                    title="Subscription"
                    description="Manage your plan, billing, and invoices"
                    :href="provider.subscription.index.url()"
                />
            </div>
        </div>
    </SettingsLayout>
</template>

<style scoped>
.settings-page {
    max-width: 900px;
}

.page-subtitle {
    margin: 0 0 1.5rem;
    font-size: 0.9375rem;
    color: var(--color-slate-500, #64748b);
}

.settings-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
}

@media (min-width: 768px) {
    .settings-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
