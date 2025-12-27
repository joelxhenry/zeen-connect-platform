<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleStatCard,
    ConsoleEmptyState,
    ConsoleDataCard,
} from '@/components/console';
import AppLink from '@/components/common/AppLink.vue';
import Select from 'primevue/select';
import DatePicker from 'primevue/datepicker';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import type { RefundsProps } from '@/types/payments';

const props = defineProps<RefundsProps>();

const selectedStatus = ref(props.filters.status);
const dateFrom = ref<Date | null>(props.filters.date_from ? new Date(props.filters.date_from) : null);
const dateTo = ref<Date | null>(props.filters.date_to ? new Date(props.filters.date_to) : null);

watch([selectedStatus, dateFrom, dateTo], () => {
    router.get('/payments/refunds', {
        status: selectedStatus.value,
        date_from: dateFrom.value?.toISOString().split('T')[0],
        date_to: dateTo.value?.toISOString().split('T')[0],
    }, {
        preserveState: true,
        preserveScroll: true,
    });
});

const getStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'secondary' => {
    switch (status) {
        case 'refunded':
            return 'secondary';
        case 'partially_refunded':
            return 'warn';
        default:
            return 'secondary';
    }
};

const clearFilters = () => {
    selectedStatus.value = 'all';
    dateFrom.value = null;
    dateTo.value = null;
};
</script>

<template>
    <ConsoleLayout title="Refunds">
        <div class="w-full max-w-5xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Refunds"
                subtitle="View and manage refunded payments"
                back-href="/payments"
            />

            <!-- Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <ConsoleStatCard
                    title="Total Refunds"
                    :value="stats.total_count.toString()"
                    icon="pi pi-replay"
                    icon-color="warning"
                />
                <ConsoleStatCard
                    title="Total Refunded"
                    :value="stats.total_amount_display"
                    icon="pi pi-dollar"
                    icon-color="danger"
                />
                <ConsoleStatCard
                    title="Refund Rate"
                    :value="stats.refund_rate_display"
                    icon="pi pi-percentage"
                    icon-color="purple"
                />
            </div>

            <!-- Filters -->
            <ConsoleFormCard class="mb-6">
                <div class="flex flex-col sm:flex-row gap-4">
                    <Select
                        v-model="selectedStatus"
                        :options="statusOptions"
                        optionLabel="label"
                        optionValue="value"
                        placeholder="Filter by status"
                        class="w-full sm:w-48"
                    />
                    <DatePicker
                        v-model="dateFrom"
                        placeholder="From date"
                        dateFormat="M dd, yy"
                        showIcon
                        class="flex-1"
                    />
                    <DatePicker
                        v-model="dateTo"
                        placeholder="To date"
                        dateFormat="M dd, yy"
                        showIcon
                        class="flex-1"
                    />
                    <Button
                        v-if="selectedStatus !== 'all' || dateFrom || dateTo"
                        icon="pi pi-times"
                        severity="secondary"
                        outlined
                        v-tooltip="'Clear Filters'"
                        @click="clearFilters"
                    />
                </div>
            </ConsoleFormCard>

            <!-- Empty State -->
            <ConsoleEmptyState
                v-if="!refunds.data?.length"
                icon="pi pi-replay"
                title="No refunds found"
                description="Refunded payments matching your filters will appear here."
            />

            <!-- Refunds List -->
            <div v-else class="space-y-4">
                <ConsoleDataCard
                    v-for="refund in refunds.data"
                    :key="refund.uuid"
                >
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-xl bg-yellow-500/10 flex items-center justify-center shrink-0">
                            <i class="pi pi-replay text-yellow-600" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 flex-wrap mb-1">
                                <h3 class="font-semibold text-[#0D1F1B] m-0">{{ refund.service_name }}</h3>
                                <Tag
                                    :value="refund.is_partial ? 'Partial' : 'Full'"
                                    :severity="refund.is_partial ? 'warn' : 'secondary'"
                                    class="!text-xs"
                                />
                                <Tag
                                    :value="refund.status_label"
                                    :severity="getStatusSeverity(refund.status)"
                                    class="!text-xs"
                                />
                            </div>
                            <p class="text-sm text-gray-500 m-0">
                                {{ refund.client_name }} &bull; {{ refund.booking_date }}
                            </p>
                            <p v-if="refund.refund_reason" class="text-sm text-gray-400 m-0 mt-1 italic">
                                "{{ refund.refund_reason }}"
                            </p>
                        </div>

                        <div class="text-right shrink-0">
                            <p class="font-semibold text-red-600 m-0 text-lg">
                                -{{ refund.original_amount_display }}
                            </p>
                            <p class="text-xs text-gray-400 m-0">
                                Your portion: {{ refund.provider_amount_display }}
                            </p>
                        </div>

                        <AppLink :href="`/bookings/${refund.booking_uuid}`">
                            <Button
                                icon="pi pi-eye"
                                size="small"
                                severity="secondary"
                                outlined
                                v-tooltip="'View Booking'"
                            />
                        </AppLink>
                    </div>

                    <template #footer>
                        <div class="flex items-center gap-4 text-xs text-gray-400">
                            <span v-if="refund.paid_at">
                                <i class="pi pi-credit-card mr-1" />
                                Paid {{ refund.paid_at }}
                            </span>
                            <span v-if="refund.refunded_at">
                                <i class="pi pi-replay mr-1" />
                                Refunded {{ refund.refunded_at }}
                            </span>
                            <span v-if="refund.refund_transaction_id" class="font-mono">
                                Ref: {{ refund.refund_transaction_id }}
                            </span>
                        </div>
                    </template>
                </ConsoleDataCard>
            </div>

            <!-- Pagination -->
            <div v-if="refunds.meta?.last_page && refunds.meta.last_page > 1" class="flex justify-center gap-2 mt-6">
                <AppLink
                    v-for="link in refunds.meta.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    :class="[
                        'px-3 py-2 rounded-lg text-sm no-underline',
                        link.active ? 'bg-[#106B4F] text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200',
                        !link.url && 'opacity-50 pointer-events-none',
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </ConsoleLayout>
</template>
