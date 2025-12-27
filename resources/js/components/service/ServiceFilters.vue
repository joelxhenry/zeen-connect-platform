<script setup lang="ts">
import { computed } from 'vue';
import { ConsoleFormCard } from '@/components/console';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';
import type { Category } from '@/types/service';

interface Props {
    categories: Category[];
}

const props = defineProps<Props>();

const searchQuery = defineModel<string>('search', { default: '' });
const selectedCategory = defineModel<number | null>('category', { default: null });
const selectedStatus = defineModel<'all' | 'active' | 'inactive'>('status', { default: 'all' });

const emit = defineEmits<{
    clear: [];
}>();

const categoryOptions = computed(() => [
    { value: null, label: 'All Categories' },
    ...props.categories.map(cat => ({ value: cat.id, label: cat.name })),
]);

const statusOptions = [
    { value: 'all', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const hasActiveFilters = computed(() => {
    return searchQuery.value || selectedCategory.value !== null || selectedStatus.value !== 'all';
});

const clearFilters = () => {
    searchQuery.value = '';
    selectedCategory.value = null;
    selectedStatus.value = 'all';
    emit('clear');
};
</script>

<template>
    <ConsoleFormCard>
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <InputText
                    v-model="searchQuery"
                    placeholder="Search services..."
                    class="w-full"
                >
                    <template #prefix>
                        <i class="pi pi-search text-gray-400" />
                    </template>
                </InputText>
            </div>
            <Select
                v-model="selectedCategory"
                :options="categoryOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full sm:w-48"
            />
            <Select
                v-model="selectedStatus"
                :options="statusOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full sm:w-36"
            />
            <Button
                v-if="hasActiveFilters"
                icon="pi pi-times"
                severity="secondary"
                outlined
                v-tooltip="'Clear filters'"
                @click="clearFilters"
            />
        </div>
    </ConsoleFormCard>
</template>
