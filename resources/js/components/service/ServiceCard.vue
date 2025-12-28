<script setup lang="ts">
import { computed } from 'vue';
import { ConsoleDataCard } from '@/components/console';
import AppLink from '@/components/common/AppLink.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import type { Service } from '@/types/service';

interface Props {
    service: Service;
    editUrl: string;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    toggleActive: [service: Service];
    delete: [service: Service];
}>();

const placeholderBackground = computed(() => {
    const hue = props.service.name.charCodeAt(0) * 10 % 360;
    return `linear-gradient(135deg, hsl(${hue}, 40%, 85%) 0%, hsl(${hue}, 50%, 75%) 100%)`;
});
</script>

<template>
    <ConsoleDataCard class="flex flex-col">
        <!-- Cover Image -->
        <div class="relative -mx-4 -mt-4 mb-4 h-32 overflow-hidden rounded-t-xl">
            <img
                v-if="service.cover?.thumbnail"
                :src="service.cover.thumbnail"
                :alt="service.name"
                class="w-full h-full object-cover"
            />
            <div
                v-else
                class="w-full h-full flex items-center justify-center"
                :style="{ background: placeholderBackground }"
            >
                <i class="pi pi-image text-3xl text-white/60" />
            </div>
            <!-- Status Badge -->
            <div class="absolute top-3 right-3">
                <Tag
                    :value="service.is_active ? 'Active' : 'Inactive'"
                    :severity="service.is_active ? 'success' : 'warn'"
                />
            </div>
        </div>

        <!-- Content -->
        <div class="flex-1 flex flex-col">
            <div class="flex items-start justify-between mb-2">
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-[#0D1F1B] m-0 mb-1 truncate">
                        {{ service.name }}
                    </h3>
                    <Tag
                        v-if="service.category"
                        :value="service.category.name"
                        severity="secondary"
                        class="!text-xs"
                    />
                </div>
                <div class="text-right shrink-0 ml-3">
                    <p class="font-bold text-[#106B4F] m-0 text-lg">
                        {{ service.price_display }}
                    </p>
                </div>
            </div>

            <p v-if="service.description" class="text-sm text-gray-500 m-0 mb-3 line-clamp-2">
                {{ service.description }}
            </p>

            <div class="flex items-center gap-4 text-sm text-gray-600 mt-auto">
                <div class="flex items-center gap-1.5">
                    <i class="pi pi-clock text-xs text-gray-400" />
                    <span>{{ service.duration_display }}</span>
                </div>
                <div v-if="service.total_bookings !== undefined" class="flex items-center gap-1.5">
                    <i class="pi pi-calendar text-xs text-gray-400" />
                    <span>{{ service.total_bookings }} bookings</span>
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex items-center gap-2">
                <AppLink :href="editUrl" class="flex-1">
                    <Button
                        label="Edit"
                        icon="pi pi-pencil"
                        size="small"
                        severity="secondary"
                        outlined
                        class="w-full"
                    />
                </AppLink>
                <Button
                    :icon="service.is_active ? 'pi pi-pause' : 'pi pi-play'"
                    size="small"
                    :severity="service.is_active ? 'warn' : 'success'"
                    outlined
                    v-tooltip="service.is_active ? 'Deactivate' : 'Activate'"
                    @click="emit('toggleActive', service)"
                />
                <Button
                    icon="pi pi-trash"
                    size="small"
                    severity="danger"
                    outlined
                    v-tooltip="'Delete'"
                    @click="emit('delete', service)"
                />
            </div>
        </template>
    </ConsoleDataCard>
</template>
