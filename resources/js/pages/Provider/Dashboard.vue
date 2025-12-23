<script setup lang="ts">
import { usePage, Link } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import type { PageProps } from '@/types/models';
import Button from 'primevue/button';
import ProgressBar from 'primevue/progressbar';

const page = usePage<PageProps>();
const user = page.props.auth?.user;
const provider = page.props.auth?.provider;

// Setup checklist items
const setupItems = [
    { id: 'account', label: 'Create your account', icon: 'pi-user-plus', completed: true, route: '' },
    { id: 'profile', label: 'Complete your profile', icon: 'pi-id-card', completed: !!provider?.bio, route: route('provider.profile.edit') },
    { id: 'services', label: 'Add your first service', icon: 'pi-box', completed: false, route: route('provider.services.index') },
    { id: 'availability', label: 'Set your availability', icon: 'pi-calendar', completed: false, route: '/console/availability' },
    { id: 'portfolio', label: 'Upload portfolio images', icon: 'pi-images', completed: false, route: '/console/portfolios' },
];

const completedCount = setupItems.filter(item => item.completed).length;
const setupProgress = Math.round((completedCount / setupItems.length) * 100);

const stats = [
    { label: 'Pending', value: '0', icon: 'pi-clock', color: 'orange' },
    { label: 'Completed', value: '0', icon: 'pi-check-circle', color: 'green' },
    { label: 'Earnings', value: '$0', icon: 'pi-wallet', color: 'purple' },
    { label: 'Rating', value: '--', icon: 'pi-star-fill', color: 'yellow' },
];

const quickActions = [
    { label: 'Add Service', icon: 'pi-plus', route: route('provider.services.create'), desc: 'Create a new service' },
    { label: 'Set Hours', icon: 'pi-clock', route: '/console/availability', desc: 'Manage availability' },
    { label: 'Upload Photos', icon: 'pi-images', route: '/console/portfolios', desc: 'Show your work' },
    { label: 'Edit Profile', icon: 'pi-user-edit', route: route('provider.profile.edit'), desc: 'Update your info' },
];
</script>

<template>
    <ConsoleLayout title="Dashboard">
        <div>
            <!-- Welcome Banner -->
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 p-6 bg-gradient-to-r from-[var(--p-primary-color)] to-[var(--p-primary-400)] rounded-2xl mb-6">
                <div class="flex items-center gap-4">
                    <div class="w-13 h-13 bg-white/20 rounded-xl flex items-center justify-center text-xl font-bold text-white">
                        {{ user?.name?.charAt(0) || 'U' }}
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-white m-0">Welcome back, {{ user?.name?.split(' ')[0] }}!</h1>
                        <p class="text-sm text-white/80 mt-1 m-0">Here's an overview of your business</p>
                    </div>
                </div>
                <Button
                    label="View Public Profile"
                    icon="pi pi-external-link"
                    severity="secondary"
                    outlined
                    size="small"
                    class="welcome-action w-full sm:w-auto"
                />
            </div>

            <!-- Stats Row -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-4 mb-6">
                <div v-for="stat in stats" :key="stat.label" class="flex items-center gap-3 p-4 bg-[var(--p-surface-0)] border border-[var(--p-surface-200)] rounded-xl">
                    <div
                        class="w-10 h-10 rounded-lg flex items-center justify-center text-base"
                        :class="{
                            'bg-orange-50 text-orange-500': stat.color === 'orange',
                            'bg-emerald-50 text-emerald-500': stat.color === 'green',
                            'bg-violet-50 text-violet-500': stat.color === 'purple',
                            'bg-amber-50 text-amber-500': stat.color === 'yellow'
                        }"
                    >
                        <i :class="`pi ${stat.icon}`"></i>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xl font-bold text-[var(--p-surface-900)] leading-tight">{{ stat.value }}</span>
                        <span class="text-xs text-[var(--p-surface-500)] mt-0.5">{{ stat.label }}</span>
                    </div>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6">
                <!-- Left Column -->
                <div class="flex flex-col gap-6">
                    <!-- Recent Activity -->
                    <div class="bg-[var(--p-surface-0)] border border-[var(--p-surface-200)] rounded-xl overflow-hidden">
                        <div class="flex justify-between items-center px-5 py-4 border-b border-[var(--p-surface-100)]">
                            <h2 class="text-[0.9375rem] font-semibold text-[var(--p-surface-900)] m-0">Recent Activity</h2>
                            <Link href="/console/bookings">
                                <Button label="View all" text size="small" icon="pi pi-arrow-right" iconPos="right" />
                            </Link>
                        </div>
                        <div class="p-5">
                            <div class="text-center py-8 px-4">
                                <div class="w-16 h-16 bg-[var(--p-surface-100)] rounded-2xl flex items-center justify-center mx-auto mb-4">
                                    <i class="pi pi-inbox text-2xl text-[var(--p-surface-400)]"></i>
                                </div>
                                <h3 class="text-[0.9375rem] font-semibold text-[var(--p-surface-900)] m-0 mb-1.5">No activity yet</h3>
                                <p class="text-[0.8125rem] text-[var(--p-surface-500)] m-0">Your recent bookings and messages will appear here</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-[var(--p-surface-0)] border border-[var(--p-surface-200)] rounded-xl overflow-hidden">
                        <div class="flex justify-between items-center px-5 py-4 border-b border-[var(--p-surface-100)]">
                            <h2 class="text-[0.9375rem] font-semibold text-[var(--p-surface-900)] m-0">Quick Actions</h2>
                        </div>
                        <div class="p-5">
                            <div class="flex flex-col gap-2">
                                <Link
                                    v-for="action in quickActions"
                                    :key="action.label"
                                    :href="action.route"
                                    class="flex items-center gap-3.5 p-3.5 rounded-lg no-underline transition-colors hover:bg-[var(--p-surface-50)]"
                                >
                                    <div class="w-10 h-10 bg-gradient-to-br from-[var(--p-primary-color)] to-[var(--p-primary-400)] rounded-lg flex items-center justify-center text-white text-base shrink-0">
                                        <i :class="`pi ${action.icon}`"></i>
                                    </div>
                                    <div class="flex-1 flex flex-col">
                                        <span class="text-sm font-medium text-[var(--p-surface-900)]">{{ action.label }}</span>
                                        <span class="text-xs text-[var(--p-surface-500)] mt-0.5">{{ action.desc }}</span>
                                    </div>
                                    <i class="pi pi-chevron-right text-[var(--p-surface-400)] text-xs"></i>
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="flex flex-col gap-6">
                    <!-- Setup Progress -->
                    <div class="bg-[var(--p-surface-0)] border border-[var(--p-surface-200)] rounded-xl overflow-hidden">
                        <div class="flex justify-between items-center px-5 py-4 border-b border-[var(--p-surface-100)]">
                            <h2 class="text-[0.9375rem] font-semibold text-[var(--p-surface-900)] m-0">Complete Your Profile</h2>
                            <span class="text-xs font-semibold text-[var(--p-primary-color)] bg-[var(--p-primary-50)] px-2.5 py-1 rounded-full">{{ setupProgress }}%</span>
                        </div>
                        <div class="p-5 pt-4">
                            <ProgressBar :value="setupProgress" :showValue="false" class="h-1.5 rounded mb-5" />

                            <div class="flex flex-col">
                                <div
                                    v-for="item in setupItems"
                                    :key="item.id"
                                    class="flex items-center gap-3 py-3 border-b border-[var(--p-surface-100)] last:border-b-0 first:pt-0 last:pb-0"
                                >
                                    <div
                                        class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0"
                                        :class="item.completed ? 'bg-emerald-50 text-emerald-500' : 'bg-[var(--p-surface-100)] text-[var(--p-surface-500)]'"
                                    >
                                        <i :class="item.completed ? 'pi pi-check' : `pi ${item.icon}`"></i>
                                    </div>
                                    <div class="flex-1">
                                        <span
                                            class="text-[0.8125rem]"
                                            :class="item.completed ? 'text-[var(--p-surface-500)] line-through' : 'text-[var(--p-surface-700)]'"
                                        >{{ item.label }}</span>
                                    </div>
                                    <Link v-if="!item.completed && item.route" :href="item.route">
                                        <Button label="Start" size="small" text />
                                    </Link>
                                    <span v-else-if="item.completed" class="w-6 h-6 bg-emerald-500 rounded-full flex items-center justify-center text-white text-[0.625rem]">
                                        <i class="pi pi-check"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="flex items-start gap-4 p-5 bg-gradient-to-br from-amber-100 to-yellow-100 border border-amber-300 rounded-xl">
                        <div class="w-10 h-10 bg-amber-500/15 rounded-lg flex items-center justify-center text-amber-600 text-lg shrink-0">
                            <i class="pi pi-lightbulb"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-sm font-semibold text-amber-900 m-0 mb-1.5">Pro Tip</h3>
                            <p class="text-[0.8125rem] text-amber-700 m-0 leading-relaxed">Complete your profile to increase visibility and attract more clients. Providers with complete profiles get 3x more bookings!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.welcome-action {
    background-color: rgba(255, 255, 255, 0.15) !important;
    border-color: rgba(255, 255, 255, 0.3) !important;
    color: white !important;
}

.welcome-action:hover {
    background-color: rgba(255, 255, 255, 0.25) !important;
}
</style>
