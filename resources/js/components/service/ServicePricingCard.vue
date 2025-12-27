<script setup lang="ts">
import { ConsoleFormCard, ConsoleFormSection } from '@/components/console';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import type { DurationOption, ValidationResult } from '@/types/service';

interface Props {
    durationOptions: DurationOption[];
    priceValidation: ValidationResult;
    priceHelpText?: string;
    errors?: {
        duration_minutes?: string;
        price?: string;
    };
}

defineProps<Props>();

const durationMinutes = defineModel<number>('durationMinutes', { required: true });
const price = defineModel<number | null>('price', { required: true });
</script>

<template>
    <ConsoleFormCard title="Pricing & Duration" icon="pi pi-dollar">
        <div class="space-y-4">
            <ConsoleFormSection :columns="2">
                <!-- Duration -->
                <div>
                    <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">
                        Duration *
                    </label>
                    <Select
                        id="duration"
                        v-model="durationMinutes"
                        :options="durationOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Select duration"
                        class="w-full"
                        :class="{ 'p-invalid': errors?.duration_minutes }"
                    />
                    <small v-if="errors?.duration_minutes" class="text-red-500">{{ errors.duration_minutes }}</small>
                </div>

                <!-- Price -->
                <div>
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">
                        Price (JMD) *
                    </label>
                    <InputNumber
                        id="price"
                        v-model="price"
                        mode="currency"
                        currency="JMD"
                        locale="en-JM"
                        class="w-full"
                        :class="{ 'p-invalid': errors?.price || !priceValidation.valid }"
                        placeholder="0.00"
                    />
                    <small v-if="errors?.price" class="text-red-500">{{ errors.price }}</small>
                    <small v-else-if="!priceValidation.valid" class="text-red-500">{{ priceValidation.message }}</small>
                    <small v-else-if="priceHelpText" class="text-gray-500">{{ priceHelpText }}</small>
                </div>
            </ConsoleFormSection>
        </div>
    </ConsoleFormCard>
</template>
