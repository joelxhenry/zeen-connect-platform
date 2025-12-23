<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Button from 'primevue/button';

interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon?: string;
}

interface Region {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    locations: Array<{ id: number; name: string; slug: string }>;
}

interface Filters {
    search?: string;
    category?: number;
    region?: number;
    location?: number;
    sort?: string;
}

const props = defineProps<{
    categories: Category[];
    regions: Region[];
    filters: Filters;
}>();

const emit = defineEmits<{
    (e: 'update:filters', filters: Filters): void;
}>();

const search = ref(props.filters.search || '');
const selectedCategory = ref<number | null>(props.filters.category || null);
const selectedRegion = ref<number | null>(props.filters.region || null);
const selectedLocation = ref<number | null>(props.filters.location || null);
const selectedSort = ref(props.filters.sort || 'featured');

const sortOptions = [
    { label: 'Featured', value: 'featured' },
    { label: 'Highest Rated', value: 'rating' },
    { label: 'Newest', value: 'newest' },
    { label: 'Name (A-Z)', value: 'name' },
];

const availableLocations = ref<Array<{ id: number; name: string }>>([]);

watch(selectedRegion, (regionId) => {
    if (regionId) {
        const region = props.regions.find(r => r.id === regionId);
        availableLocations.value = region?.locations || [];
    } else {
        availableLocations.value = [];
    }
    selectedLocation.value = null;
});

// Initialize locations if region is pre-selected
if (props.filters.region) {
    const region = props.regions.find(r => r.id === props.filters.region);
    availableLocations.value = region?.locations || [];
}

let searchTimeout: ReturnType<typeof setTimeout>;

const applyFilters = () => {
    const filters: Record<string, unknown> = {};

    if (search.value) filters.search = search.value;
    if (selectedCategory.value) filters.category = selectedCategory.value;
    if (selectedRegion.value) filters.region = selectedRegion.value;
    if (selectedLocation.value) filters.location = selectedLocation.value;
    if (selectedSort.value && selectedSort.value !== 'featured') filters.sort = selectedSort.value;

    router.get(route('explore'), filters, {
        preserveState: true,
        preserveScroll: true,
    });
};

const handleSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
};

const clearFilters = () => {
    search.value = '';
    selectedCategory.value = null;
    selectedRegion.value = null;
    selectedLocation.value = null;
    selectedSort.value = 'featured';
    router.get(route('explore'));
};

const hasActiveFilters = () => {
    return search.value || selectedCategory.value || selectedRegion.value || selectedLocation.value || (selectedSort.value && selectedSort.value !== 'featured');
};

watch([selectedCategory, selectedRegion, selectedLocation, selectedSort], () => {
    applyFilters();
});
</script>

<template>
    <aside class="filter-sidebar">
        <div class="filter-header">
            <h2 class="filter-title">Filters</h2>
            <Button
                v-if="hasActiveFilters()"
                type="button"
                label="Clear all"
                severity="secondary"
                text
                size="small"
                @click="clearFilters"
            />
        </div>

        <div class="filter-section">
            <label class="filter-label">Search</label>
            <span class="p-input-icon-left w-full">
                <i class="pi pi-search" />
                <InputText
                    v-model="search"
                    placeholder="Search providers..."
                    class="w-full"
                    @input="handleSearchInput"
                />
            </span>
        </div>

        <div class="filter-section">
            <label class="filter-label">Category</label>
            <Select
                v-model="selectedCategory"
                :options="categories"
                optionLabel="name"
                optionValue="id"
                placeholder="All categories"
                class="w-full"
                showClear
            >
                <template #option="{ option }">
                    <div class="flex items-center gap-2">
                        <i v-if="option.icon" :class="`pi ${option.icon}`" class="text-[var(--p-surface-500)]"></i>
                        <span>{{ option.name }}</span>
                    </div>
                </template>
            </Select>
        </div>

        <div class="filter-section">
            <label class="filter-label">Parish/Region</label>
            <Select
                v-model="selectedRegion"
                :options="regions"
                optionLabel="name"
                optionValue="id"
                placeholder="All parishes"
                class="w-full"
                showClear
            />
        </div>

        <div class="filter-section" v-if="availableLocations.length > 0">
            <label class="filter-label">Location</label>
            <Select
                v-model="selectedLocation"
                :options="availableLocations"
                optionLabel="name"
                optionValue="id"
                placeholder="All locations"
                class="w-full"
                showClear
            />
        </div>

        <div class="filter-section">
            <label class="filter-label">Sort by</label>
            <Select
                v-model="selectedSort"
                :options="sortOptions"
                optionLabel="label"
                optionValue="value"
                class="w-full"
            />
        </div>
    </aside>
</template>

<style scoped>
.filter-sidebar {
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 16px;
    padding: 1.25rem;
    position: sticky;
    top: 1rem;
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.25rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--p-surface-200);
}

.filter-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0;
}

.filter-section {
    margin-bottom: 1.25rem;
}

.filter-section:last-child {
    margin-bottom: 0;
}

.filter-label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--p-surface-700);
    margin-bottom: 0.5rem;
}

.p-input-icon-left {
    position: relative;
    display: inline-flex;
    width: 100%;
}

.p-input-icon-left > i {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--p-surface-400);
}

.p-input-icon-left > input {
    padding-left: 2.25rem;
}
</style>
