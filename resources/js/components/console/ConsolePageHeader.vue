<script setup lang="ts">
import AppLink from '@/components/common/AppLink.vue';

interface Breadcrumb {
    label: string;
    href?: string;
}

interface Props {
    title: string;
    subtitle?: string;
    icon?: string;
    backHref?: string;
    breadcrumbs?: Breadcrumb[];
}

defineProps<Props>();
</script>

<template>
    <div class="mb-6">
        <!-- Breadcrumbs -->
        <nav v-if="breadcrumbs?.length" class="flex items-center gap-2 text-sm text-gray-500 mb-3">
            <template v-for="(crumb, index) in breadcrumbs" :key="index">
                <AppLink
                    v-if="crumb.href"
                    :href="crumb.href"
                    class="hover:text-[#106B4F] transition-colors"
                >
                    {{ crumb.label }}
                </AppLink>
                <span v-else>{{ crumb.label }}</span>
                <i v-if="index < breadcrumbs.length - 1" class="pi pi-chevron-right text-xs" />
            </template>
        </nav>

        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex items-start gap-3">
                <!-- Back arrow -->
                <AppLink
                    v-if="backHref"
                    :href="backHref"
                    class="mt-1 w-8 h-8 flex items-center justify-center rounded-lg text-gray-500 hover:text-[#106B4F] hover:bg-[#106B4F]/5 transition-all"
                >
                    <i class="pi pi-arrow-left" />
                </AppLink>

                <div>
                    <!-- Title row -->
                    <div class="flex items-center gap-3">
                        <div
                            v-if="icon"
                            class="w-10 h-10 rounded-xl bg-[#106B4F]/10 flex items-center justify-center shrink-0"
                        >
                            <i :class="[icon, 'text-[#106B4F] text-lg']" />
                        </div>

                        <div>
                            <h1 class="text-xl lg:text-2xl font-semibold text-[#0D1F1B] m-0">
                                {{ title }}
                            </h1>
                            <p v-if="subtitle" class="text-gray-500 text-sm lg:text-base m-0 mt-0.5">
                                {{ subtitle }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions slot -->
            <div v-if="$slots.actions" class="flex items-center gap-2 sm:gap-3 shrink-0">
                <slot name="actions" />
            </div>
        </div>

        <!-- Badge slot (for status indicators, ratings, etc.) -->
        <div v-if="$slots.badge" class="mt-3">
            <slot name="badge" />
        </div>
    </div>
</template>
