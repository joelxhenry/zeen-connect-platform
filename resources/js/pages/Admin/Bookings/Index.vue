<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/components/layout/AdminLayout.vue';
import Card from 'primevue/card';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import DatePicker from 'primevue/datepicker';

interface Booking {
    id: number;
    uuid: string;
    client: {
        name: string;
        email: string;
    };
    provider: {
        business_name: string;
    };
    service: {
        name: string;
    };
    booking_date: string;
    start_time: string;
    end_time: string;
    status: string;
    status_label: string;
    total_amount: string;
    created_at: string;
}

interface PaginatedBookings {
    data: Booking[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Status {
    value: string;
    label: string;
    color: string;
}

const props = defineProps<{
    bookings: PaginatedBookings;
    filters: {
        search: string | null;
        status: string | null;
        date_from: string | null;
        date_to: string | null;
        sort: string;
        dir: string;
    };
    statuses: Status[];
}>();

const search = ref(props.filters.search || '');
const selectedStatus = ref(props.filters.status || '');
const dateFrom = ref(props.filters.date_from ? new Date(props.filters.date_from) : null);
const dateTo = ref(props.filters.date_to ? new Date(props.filters.date_to) : null);

const showStatusDialog = ref(false);
const selectedBooking = ref<Booking | null>(null);
const newStatus = ref('');

const statusOptions = [
    { value: '', label: 'All Status' },
    ...props.statuses,
];

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('admin.bookings.index'), {
            search: search.value || undefined,
            status: selectedStatus.value || undefined,
            date_from: dateFrom.value ? formatDate(dateFrom.value) : undefined,
            date_to: dateTo.value ? formatDate(dateTo.value) : undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

const formatDate = (date: Date): string => {
    return date.toISOString().split('T')[0];
};

watch([search, selectedStatus, dateFrom, dateTo], applyFilters);

const getStatusSeverity = (status: string) => {
    const severities: Record<string, string> = {
        pending: 'warning',
        confirmed: 'info',
        completed: 'success',
        cancelled: 'danger',
        no_show: 'secondary',
    };
    return severities[status] || 'secondary';
};

const openStatusDialog = (booking: Booking) => {
    selectedBooking.value = booking;
    newStatus.value = booking.status;
    showStatusDialog.value = true;
};

const updateStatus = () => {
    if (selectedBooking.value && newStatus.value) {
        router.put(route('admin.bookings.status', selectedBooking.value.uuid), {
            status: newStatus.value,
        }, {
            preserveScroll: true,
            onSuccess: () => {
                showStatusDialog.value = false;
                selectedBooking.value = null;
            },
        });
    }
};

const clearFilters = () => {
    search.value = '';
    selectedStatus.value = '';
    dateFrom.value = null;
    dateTo.value = null;
};
</script>

<template>
    <AdminLayout title="Bookings">
        <Head title="Booking Management" />

        <Dialog
            v-model:visible="showStatusDialog"
            modal
            header="Update Booking Status"
            :style="{ width: '400px' }"
        >
            <div class="status-dialog">
                <p>Update status for booking on <strong>{{ selectedBooking?.booking_date }}</strong></p>
                <Select
                    v-model="newStatus"
                    :options="statuses"
                    optionLabel="label"
                    optionValue="value"
                    placeholder="Select status"
                    class="w-full"
                />
            </div>
            <template #footer>
                <Button label="Cancel" severity="secondary" @click="showStatusDialog = false" />
                <Button label="Update" @click="updateStatus" />
            </template>
        </Dialog>

        <div class="bookings-page">
            <Card>
                <template #title>
                    <div class="card-header">
                        <div class="header-left">
                            <h2>Booking Management</h2>
                            <Tag :value="`${bookings.total} bookings`" severity="secondary" />
                        </div>
                    </div>
                </template>

                <template #content>
                    <!-- Filters -->
                    <div class="filters">
                        <div class="search-box">
                            <i class="pi pi-search"></i>
                            <InputText
                                v-model="search"
                                placeholder="Search by client, provider, or booking ID..."
                                class="search-input"
                            />
                        </div>

                        <Select
                            v-model="selectedStatus"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filter by status"
                            class="filter-select"
                        />

                        <DatePicker
                            v-model="dateFrom"
                            placeholder="From date"
                            dateFormat="M d, yy"
                            showIcon
                            class="date-picker"
                        />

                        <DatePicker
                            v-model="dateTo"
                            placeholder="To date"
                            dateFormat="M d, yy"
                            showIcon
                            class="date-picker"
                        />

                        <Button
                            v-if="search || selectedStatus || dateFrom || dateTo"
                            icon="pi pi-times"
                            severity="secondary"
                            text
                            rounded
                            @click="clearFilters"
                            v-tooltip.top="'Clear filters'"
                        />
                    </div>

                    <!-- Bookings Table -->
                    <DataTable
                        :value="bookings.data"
                        :paginator="false"
                        :rows="20"
                        class="bookings-table"
                        stripedRows
                    >
                        <Column header="Client" style="min-width: 200px">
                            <template #body="{ data }">
                                <div class="client-cell">
                                    <span class="client-name">{{ data.client.name }}</span>
                                    <span class="client-email">{{ data.client.email }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Provider">
                            <template #body="{ data }">
                                {{ data.provider.business_name }}
                            </template>
                        </Column>

                        <Column header="Service">
                            <template #body="{ data }">
                                {{ data.service.name }}
                            </template>
                        </Column>

                        <Column header="Date & Time" style="min-width: 180px">
                            <template #body="{ data }">
                                <div class="datetime-cell">
                                    <span class="date">{{ data.booking_date }}</span>
                                    <span class="time">{{ data.start_time }} - {{ data.end_time }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column header="Status">
                            <template #body="{ data }">
                                <Tag :severity="getStatusSeverity(data.status)" :value="data.status_label" />
                            </template>
                        </Column>

                        <Column header="Amount">
                            <template #body="{ data }">
                                {{ data.total_amount }}
                            </template>
                        </Column>

                        <Column header="Actions" style="width: 120px">
                            <template #body="{ data }">
                                <div class="action-buttons">
                                    <Link :href="route('admin.bookings.show', data.uuid)">
                                        <Button
                                            icon="pi pi-eye"
                                            text
                                            rounded
                                            size="small"
                                            v-tooltip.top="'View'"
                                        />
                                    </Link>
                                    <Button
                                        icon="pi pi-pencil"
                                        text
                                        rounded
                                        size="small"
                                        @click="openStatusDialog(data)"
                                        v-tooltip.top="'Update Status'"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="pagination" v-if="bookings.last_page > 1">
                        <Link
                            v-for="link in bookings.links"
                            :key="link.label"
                            :href="link.url || ''"
                            :class="['page-link', { active: link.active, disabled: !link.url }]"
                            v-html="link.label"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </AdminLayout>
</template>

<style scoped>
.bookings-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.header-left h2 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.filters {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    align-items: center;
}

.search-box {
    position: relative;
    flex: 1;
    min-width: 250px;
}

.search-box i {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--p-surface-400);
}

.search-input {
    width: 100%;
    padding-left: 2.5rem;
}

.filter-select {
    min-width: 150px;
}

.date-picker {
    min-width: 150px;
}

.client-cell {
    display: flex;
    flex-direction: column;
}

.client-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.client-email {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.datetime-cell {
    display: flex;
    flex-direction: column;
}

.datetime-cell .date {
    font-weight: 500;
    color: var(--p-surface-900);
}

.datetime-cell .time {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.status-dialog {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.status-dialog p {
    margin: 0;
    color: var(--p-surface-600);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.25rem;
    margin-top: 1.5rem;
}

.page-link {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--p-surface-200);
    border-radius: 6px;
    text-decoration: none;
    color: var(--p-surface-700);
    font-size: 0.875rem;
    transition: all 0.2s;
}

.page-link:hover:not(.disabled) {
    background-color: var(--p-surface-100);
}

.page-link.active {
    background-color: var(--p-red-500);
    border-color: var(--p-red-500);
    color: white;
}

.page-link.disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .filters {
        flex-direction: column;
    }

    .search-box,
    .filter-select,
    .date-picker {
        width: 100%;
    }
}
</style>
