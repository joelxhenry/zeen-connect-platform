<script setup lang="ts">
import { ConsoleFormCard } from '@/components/console';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import type { Category } from '@/types/service';

interface Props {
    categories: Category[];
    errors?: {
        name?: string;
        category_id?: string;
        description?: string;
    };
}

defineProps<Props>();

const name = defineModel<string>('name', { required: true });
const categoryId = defineModel<number | null>('categoryId', { required: true });
const description = defineModel<string>('description', { required: true });
</script>

<template>
    <ConsoleFormCard title="Basic Information" icon="pi pi-info-circle">
        <div class="space-y-4">
            <!-- Name -->
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                    Service Name *
                </label>
                <InputText
                    id="name"
                    v-model="name"
                    class="w-full"
                    :class="{ 'p-invalid': errors?.name }"
                    placeholder="e.g., Haircut, Makeup, Massage"
                />
                <small v-if="errors?.name" class="text-red-500">{{ errors.name }}</small>
            </div>

            <!-- Category -->
            <div>
                <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                    Category *
                </label>
                <Select
                    id="category"
                    v-model="categoryId"
                    :options="categories"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select a category"
                    class="w-full"
                    :class="{ 'p-invalid': errors?.category_id }"
                />
                <small v-if="errors?.category_id" class="text-red-500">{{ errors.category_id }}</small>
            </div>

            <!-- Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">
                    Description
                </label>
                <Textarea
                    id="description"
                    v-model="description"
                    rows="3"
                    class="w-full"
                    placeholder="Describe what this service includes..."
                />
                <small v-if="errors?.description" class="text-red-500">{{ errors.description }}</small>
            </div>
        </div>
    </ConsoleFormCard>
</template>
