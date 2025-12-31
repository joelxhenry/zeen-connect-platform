<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ConsoleLayout from './ConsoleLayout.vue';
import provider from '@/routes/provider';

defineProps<{
    title?: string;
}>();

const page = usePage();
const menuCollapsed = ref(false);
const mobileMenuOpen = ref(false);

// Load collapsed state from localStorage
onMounted(() => {
    const saved = localStorage.getItem('settings-menu-collapsed');
    if (saved !== null) {
        menuCollapsed.value = saved === 'true';
    }
});

const toggleCollapse = () => {
    menuCollapsed.value = !menuCollapsed.value;
    localStorage.setItem('settings-menu-collapsed', String(menuCollapsed.value));
};

const toggleMobileMenu = () => {
    mobileMenuOpen.value = !mobileMenuOpen.value;
};

const closeMobileMenu = () => {
    mobileMenuOpen.value = false;
};

// Settings menu items grouped by section
const menuSections = computed(() => [
    {
        title: 'Business',
        items: [
            {
                label: 'My Brand',
                description: 'Logo, colors, templates',
                icon: 'pi pi-palette',
                route: provider.branding.edit.url(),
            },
            {
                label: 'Booking Settings',
                description: 'Policies, confirmations',
                icon: 'pi pi-sliders-h',
                route: provider.settings.edit.url(),
            },
            {
                label: 'Availability',
                description: 'Business hours, time off',
                icon: 'pi pi-clock',
                route: provider.availability.edit.url(),
            },
        ],
    },
    {
        title: 'Account',
        items: [
            {
                label: 'Your Profile',
                description: 'Personal info, schedule',
                icon: 'pi pi-user',
                route: provider.profile.edit.url(),
            },
            {
                label: 'Team',
                description: 'Manage team members',
                icon: 'pi pi-users',
                route: provider.team.index.url(),
            },
        ],
    },
    {
        title: 'Billing',
        items: [
            {
                label: 'Subscription',
                description: 'Plan, billing, invoices',
                icon: 'pi pi-credit-card',
                route: provider.subscription.index.url(),
            },
            {
                label: 'Payment Setup',
                description: 'Payment gateways, banking',
                icon: 'pi pi-wallet',
                route: provider.payments.setup.index.url(),
            },
        ],
    },
]);

const isActiveRoute = (route: string) => {
    const currentPath = page.url;
    const routePath = route.replace(/^\/\/[^/]+/, '');
    const normalizedCurrent = currentPath.replace(/\/$/, '');
    const normalizedRoute = routePath.replace(/\/$/, '');

    if (normalizedRoute === '' || normalizedRoute === '/') {
        return normalizedCurrent === '' || normalizedCurrent === '/';
    }
    return normalizedCurrent.startsWith(normalizedRoute);
};
</script>

<template>
    <ConsoleLayout :title="title">
        <div class="settings-layout" :class="{ 'menu-collapsed': menuCollapsed }">
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle" @click="toggleMobileMenu">
                <i class="pi pi-bars"></i>
                <span>Settings Menu</span>
                <i class="pi" :class="mobileMenuOpen ? 'pi-chevron-up' : 'pi-chevron-down'"></i>
            </button>

            <!-- Mobile Overlay -->
            <div v-if="mobileMenuOpen" class="settings-menu-overlay" @click="closeMobileMenu"></div>

            <!-- Settings Side Menu -->
            <aside class="settings-menu" :class="{ collapsed: menuCollapsed, open: mobileMenuOpen }">
                <div class="menu-header">
                    <h2 class="menu-title">Settings</h2>
                    <button class="collapse-toggle" @click="toggleCollapse" :title="menuCollapsed ? 'Expand menu' : 'Collapse menu'">
                        <i class="pi" :class="menuCollapsed ? 'pi-angle-double-right' : 'pi-angle-double-left'"></i>
                    </button>
                </div>

                <nav class="menu-nav">
                    <div v-for="section in menuSections" :key="section.title" class="menu-section">
                        <h3 class="section-title">{{ section.title }}</h3>
                        <div class="section-items">
                            <AppLink
                                v-for="item in section.items"
                                :key="item.label"
                                :href="item.route"
                                class="menu-item"
                                :class="{ active: isActiveRoute(item.route) }"
                                :title="menuCollapsed ? item.label : undefined"
                                @click="closeMobileMenu"
                            >
                                <i :class="item.icon" class="item-icon"></i>
                                <div class="item-content">
                                    <span class="item-label">{{ item.label }}</span>
                                    <span class="item-description">{{ item.description }}</span>
                                </div>
                            </AppLink>
                        </div>
                    </div>
                </nav>

                <!-- Back to Dashboard -->
                <div class="menu-footer">
                    <AppLink :href="provider.dashboard.url()" class="back-link">
                        <i class="pi pi-arrow-left"></i>
                        <span class="back-label">Back to Dashboard</span>
                    </AppLink>
                </div>
            </aside>

            <!-- Main Settings Content -->
            <main class="settings-content">
                <slot />
            </main>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.settings-layout {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 120px);
    margin: -1rem;
    background: var(--color-slate-50, #f8fafc);
}

@media (min-width: 1024px) {
    .settings-layout {
        flex-direction: row;
        margin: -1.5rem;
    }
}

/* Mobile Menu Toggle */
.mobile-menu-toggle {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.875rem 1rem;
    background: white;
    border: none;
    border-bottom: 1px solid var(--color-slate-200, #e2e8f0);
    color: var(--color-slate-700, #334155);
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    text-align: left;
}

.mobile-menu-toggle i:first-child {
    font-size: 1.125rem;
    color: var(--color-slate-500, #64748b);
}

.mobile-menu-toggle span {
    flex: 1;
}

.mobile-menu-toggle i:last-child {
    font-size: 0.875rem;
    color: var(--color-slate-400, #94a3b8);
}

@media (min-width: 1024px) {
    .mobile-menu-toggle {
        display: none;
    }
}

/* Mobile Overlay */
.settings-menu-overlay {
    position: fixed;
    top: 57px; /* Below the topbar */
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.4);
    z-index: 40;
    backdrop-filter: blur(2px);
}

@media (min-width: 1024px) {
    .settings-menu-overlay {
        display: none;
    }
}

/* Settings Menu */
.settings-menu {
    width: 280px;
    background: white;
    border-right: 1px solid var(--color-slate-200, #e2e8f0);
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    transition: all 0.3s ease;

    /* Mobile: fixed positioning below the topbar */
    position: fixed;
    top: 57px; /* Height of mobile topbar */
    left: 0;
    bottom: 0;
    z-index: 45; /* Below the main topbar (z-index: 50) */
    transform: translateX(-100%);
}

.settings-menu.open {
    transform: translateX(0);
}

@media (min-width: 1024px) {
    .settings-menu {
        position: sticky;
        top: 5rem;
        height: calc(100vh - 65px);
        z-index: 1;
        transform: translateX(0);
        overflow-y: auto;
    }

    .settings-menu.collapsed {
        width: 72px;
    }
}

/* Menu Header */
.menu-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1rem;
    border-bottom: 1px solid var(--color-slate-100, #f1f5f9);
}

.menu-title {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
    white-space: nowrap;
    overflow: hidden;
    transition: opacity 0.2s ease;
}

.settings-menu.collapsed .menu-title {
    opacity: 0;
    width: 0;
}

.collapse-toggle {
    display: none;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: none;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.375rem;
    color: var(--color-slate-500, #64748b);
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.collapse-toggle:hover {
    background: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-700, #334155);
    border-color: var(--color-slate-300, #cbd5e1);
}

@media (min-width: 1024px) {
    .collapse-toggle {
        display: flex;
    }
}

.settings-menu.collapsed .collapse-toggle {
    margin: 0 auto;
}

/* Menu Navigation */
.menu-nav {
    flex: 1;
    padding: 1rem 0;
    overflow-y: auto;
}

.menu-section {
    margin-bottom: 1.5rem;
}

.menu-section:last-child {
    margin-bottom: 0;
}

.section-title {
    margin: 0 0 0.5rem;
    padding: 0 1rem;
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-slate-400, #94a3b8);
    white-space: nowrap;
    overflow: hidden;
    transition: opacity 0.2s ease;
}

.settings-menu.collapsed .section-title {
    opacity: 0;
    height: 0;
    margin: 0;
    padding: 0;
}

.section-items {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    padding: 0 0.5rem;
}

/* Menu Item */
.menu-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.75rem;
    color: var(--color-slate-600, #475569);
    text-decoration: none;
    border-radius: 0.5rem;
    transition: all 0.15s ease;
}

.settings-menu.collapsed .menu-item {
    justify-content: center;
    padding: 0.75rem;
}

.menu-item:hover {
    background: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-900, #0f172a);
}

.menu-item.active {
    background: rgba(16, 107, 79, 0.08);
    color: #106B4F;
}

.item-icon {
    width: 1.25rem;
    min-width: 1.25rem;
    text-align: center;
    font-size: 1rem;
    color: var(--color-slate-400, #94a3b8);
    transition: color 0.15s ease;
}

.menu-item:hover .item-icon {
    color: var(--color-slate-600, #475569);
}

.menu-item.active .item-icon {
    color: #106B4F;
}

.item-content {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    overflow: hidden;
    transition: opacity 0.2s ease, width 0.2s ease;
}

.settings-menu.collapsed .item-content {
    opacity: 0;
    width: 0;
    position: absolute;
}

.item-label {
    font-size: 0.875rem;
    font-weight: 500;
    white-space: nowrap;
    line-height: 1.2;
}

.item-description {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
    white-space: nowrap;
    line-height: 1.2;
}

.menu-item.active .item-description {
    color: rgba(16, 107, 79, 0.7);
}

/* Menu Footer */
.menu-footer {
    padding: 0.75rem;
    border-top: 1px solid var(--color-slate-100, #f1f5f9);
}

.back-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.75rem;
    color: var(--color-slate-500, #64748b);
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.5rem;
    transition: all 0.15s ease;
}

.settings-menu.collapsed .back-link {
    justify-content: center;
    padding: 0.75rem;
}

.back-link:hover {
    background: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-700, #334155);
}

.back-link i {
    font-size: 0.875rem;
    min-width: 1.25rem;
    text-align: center;
}

.back-label {
    white-space: nowrap;
    transition: opacity 0.2s ease;
}

.settings-menu.collapsed .back-label {
    opacity: 0;
    width: 0;
    position: absolute;
}

/* Settings Content */
.settings-content {
    flex: 1;
    padding: 1rem;
    min-width: 0;
    overflow-x: hidden;
}

@media (min-width: 1024px) {
    .settings-content {
        padding: 1.5rem 2rem;
    }
}
</style>
