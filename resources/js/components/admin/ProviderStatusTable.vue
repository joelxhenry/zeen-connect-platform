<script setup lang="ts">
import { computed } from 'vue';
import DataTable from 'primevue/datatable';
import Column from 'primevue/column';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Button from 'primevue/button';
import Menu from 'primevue/menu';
import { ref } from 'vue';

interface Provider {
    id: number;
    uuid: string;
    business_name: string;
    user_name: string;
    user_email: string;
    avatar?: string;
    status: 'pending' | 'active' | 'suspended' | 'inactive';
    kyc_status: 'pending' | 'submitted' | 'verified' | 'rejected';
    total_bookings: number;
    created_at: string;
}

const props = defineProps<{
    providers: Provider[];
    loading?: boolean;
}>();

const emit = defineEmits<{
    (e: 'approve', provider: Provider): void;
    (e: 'reject', provider: Provider): void;
    (e: 'suspend', provider: Provider): void;
    (e: 'view', provider: Provider): void;
}>();

const activeMenu = ref();
const selectedProvider = ref<Provider | null>(null);

const getStatusConfig = (status: Provider['status']) => {
    switch (status) {
        case 'active':
            return { label: 'Active', severity: 'success' as const };
        case 'pending':
            return { label: 'Pending', severity: 'warn' as const };
        case 'suspended':
            return { label: 'Suspended', severity: 'danger' as const };
        case 'inactive':
            return { label: 'Inactive', severity: 'secondary' as const };
        default:
            return { label: status, severity: 'secondary' as const };
    }
};

const getKycConfig = (status: Provider['kyc_status']) => {
    switch (status) {
        case 'verified':
            return { label: 'Verified', severity: 'success' as const, icon: 'pi pi-verified' };
        case 'submitted':
            return { label: 'Under Review', severity: 'info' as const, icon: 'pi pi-clock' };
        case 'pending':
            return { label: 'Not Started', severity: 'secondary' as const, icon: 'pi pi-minus' };
        case 'rejected':
            return { label: 'Rejected', severity: 'danger' as const, icon: 'pi pi-times' };
        default:
            return { label: status, severity: 'secondary' as const, icon: 'pi pi-question' };
    }
};

const menuItems = computed(() => [
    {
        label: 'View Details',
        icon: 'pi pi-eye',
        command: () => selectedProvider.value && emit('view', selectedProvider.value),
    },
    {
        label: 'Approve',
        icon: 'pi pi-check',
        command: () => selectedProvider.value && emit('approve', selectedProvider.value),
        visible: selectedProvider.value?.status === 'pending',
    },
    {
        label: 'Reject',
        icon: 'pi pi-times',
        command: () => selectedProvider.value && emit('reject', selectedProvider.value),
        visible: selectedProvider.value?.status === 'pending',
    },
    { separator: true },
    {
        label: 'Suspend',
        icon: 'pi pi-ban',
        class: 'text-red-500',
        command: () => selectedProvider.value && emit('suspend', selectedProvider.value),
        visible: selectedProvider.value?.status === 'active',
    },
]);

const toggleMenu = (event: Event, provider: Provider) => {
    selectedProvider.value = provider;
    activeMenu.value.toggle(event);
};
</script>

<template>
    <div class="provider-status-table">
        <div class="table-header">
            <h3>Provider Status</h3>
            <AppLink href="/admin/providers" class="view-all-link">
                View all <i class="pi pi-arrow-right"></i>
            </AppLink>
        </div>

        <DataTable
            :value="providers"
            :loading="loading"
            stripedRows
            class="provider-table"
            :rows="5"
        >
            <Column header="Provider" style="min-width: 200px">
                <template #body="{ data }">
                    <div class="provider-cell">
                        <Avatar
                            :image="data.avatar"
                            :label="data.business_name?.charAt(0).toUpperCase()"
                            shape="circle"
                            size="normal"
                            class="provider-avatar"
                        />
                        <div class="provider-info">
                            <span class="provider-name">{{ data.business_name }}</span>
                            <span class="provider-email">{{ data.user_email }}</span>
                        </div>
                    </div>
                </template>
            </Column>

            <Column header="Status" style="width: 120px">
                <template #body="{ data }">
                    <Tag
                        :severity="getStatusConfig(data.status).severity"
                        :value="getStatusConfig(data.status).label"
                    />
                </template>
            </Column>

            <Column header="KYC" style="width: 140px">
                <template #body="{ data }">
                    <div class="kyc-badge" :class="data.kyc_status">
                        <i :class="getKycConfig(data.kyc_status).icon"></i>
                        <span>{{ getKycConfig(data.kyc_status).label }}</span>
                    </div>
                </template>
            </Column>

            <Column header="Bookings" style="width: 100px" class="text-center">
                <template #body="{ data }">
                    <span class="bookings-count">{{ data.total_bookings }}</span>
                </template>
            </Column>

            <Column header="Joined" style="width: 120px">
                <template #body="{ data }">
                    <span class="joined-date">{{ data.created_at }}</span>
                </template>
            </Column>

            <Column style="width: 60px">
                <template #body="{ data }">
                    <Button
                        icon="pi pi-ellipsis-v"
                        text
                        rounded
                        severity="secondary"
                        @click="(e) => toggleMenu(e, data)"
                    />
                </template>
            </Column>

            <template #empty>
                <div class="empty-state">
                    <i class="pi pi-inbox"></i>
                    <p>No providers found</p>
                </div>
            </template>
        </DataTable>

        <Menu ref="activeMenu" :model="menuItems" :popup="true" />
    </div>
</template>

<style scoped>
.provider-status-table {
    background: white;
    border-radius: 12px;
    padding: 1.25rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    border: 1px solid #E2E8F0;
}

.table-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.table-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: #0F172A;
}

.view-all-link {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: #106B4F;
    text-decoration: none;
    font-weight: 500;
}

.view-all-link:hover {
    text-decoration: underline;
}

.view-all-link i {
    font-size: 0.75rem;
}

.provider-table {
    border: none;
}

.provider-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.provider-avatar {
    background-color: #106B4F;
    color: white;
}

.provider-info {
    display: flex;
    flex-direction: column;
}

.provider-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: #0F172A;
}

.provider-email {
    font-size: 0.75rem;
    color: #64748B;
}

.kyc-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.75rem;
    font-weight: 500;
}

.kyc-badge.verified {
    background-color: #DCFCE7;
    color: #16A34A;
}

.kyc-badge.submitted {
    background-color: #DBEAFE;
    color: #2563EB;
}

.kyc-badge.pending {
    background-color: #F3F4F6;
    color: #6B7280;
}

.kyc-badge.rejected {
    background-color: #FEE2E2;
    color: #DC2626;
}

.bookings-count {
    font-weight: 600;
    color: #0F172A;
}

.joined-date {
    font-size: 0.8125rem;
    color: #64748B;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 3rem;
    color: #94A3B8;
}

.empty-state i {
    font-size: 2.5rem;
    margin-bottom: 0.75rem;
}

.empty-state p {
    margin: 0;
    font-size: 0.875rem;
}
</style>
