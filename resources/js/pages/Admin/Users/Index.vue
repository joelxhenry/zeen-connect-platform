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
import Avatar from 'primevue/avatar';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

interface User {
    id: number;
    uuid: string;
    name: string;
    email: string;
    phone: string | null;
    avatar: string | null;
    role: string;
    role_label: string;
    is_active: boolean;
    provider: {
        business_name: string;
        status: string;
    } | null;
    favorites_count: number;
    last_login_at: string | null;
    created_at: string;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Role {
    value: string;
    label: string;
}

const props = defineProps<{
    users: PaginatedUsers;
    filters: {
        search: string | null;
        role: string | null;
        status: string | null;
        sort: string;
        dir: string;
    };
    roles: Role[];
}>();

const confirm = useConfirm();

const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');
const selectedStatus = ref(props.filters.status || '');

const statusOptions = [
    { value: '', label: 'All Status' },
    { value: 'active', label: 'Active' },
    { value: 'inactive', label: 'Inactive' },
];

const roleOptions = [
    { value: '', label: 'All Roles' },
    ...props.roles,
];

let filterTimeout: ReturnType<typeof setTimeout> | null = null;

const applyFilters = () => {
    if (filterTimeout) clearTimeout(filterTimeout);
    filterTimeout = setTimeout(() => {
        router.get(route('admin.users.index'), {
            search: search.value || undefined,
            role: selectedRole.value || undefined,
            status: selectedStatus.value || undefined,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, 300);
};

watch([search, selectedRole, selectedStatus], applyFilters);

const toggleUserStatus = (user: User) => {
    const action = user.is_active ? 'deactivate' : 'activate';
    confirm.require({
        message: `Are you sure you want to ${action} ${user.name}?`,
        header: `${action.charAt(0).toUpperCase() + action.slice(1)} User`,
        icon: 'pi pi-exclamation-triangle',
        acceptClass: user.is_active ? 'p-button-danger' : 'p-button-success',
        accept: () => {
            router.post(route('admin.users.toggle-status', user.uuid), {}, {
                preserveScroll: true,
            });
        },
    });
};

const deleteUser = (user: User) => {
    confirm.require({
        message: `Are you sure you want to delete ${user.name}? This action cannot be undone.`,
        header: 'Delete User',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('admin.users.destroy', user.uuid));
        },
    });
};

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getRoleSeverity = (role: string) => {
    const severities: Record<string, string> = {
        admin: 'danger',
        provider: 'info',
        client: 'success',
    };
    return severities[role] || 'secondary';
};
</script>

<template>
    <AdminLayout title="Users">
        <Head title="User Management" />

        <ConfirmDialog />

        <div class="users-page">
            <Card>
                <template #title>
                    <div class="card-header">
                        <div class="header-left">
                            <h2>User Management</h2>
                            <Tag :value="`${users.total} users`" severity="secondary" />
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
                                placeholder="Search by name or email..."
                                class="search-input"
                            />
                        </div>

                        <Select
                            v-model="selectedRole"
                            :options="roleOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filter by role"
                            class="filter-select"
                        />

                        <Select
                            v-model="selectedStatus"
                            :options="statusOptions"
                            optionLabel="label"
                            optionValue="value"
                            placeholder="Filter by status"
                            class="filter-select"
                        />
                    </div>

                    <!-- Users Table -->
                    <DataTable
                        :value="users.data"
                        :paginator="false"
                        :rows="20"
                        class="users-table"
                        stripedRows
                    >
                        <Column header="User" style="min-width: 250px">
                            <template #body="{ data }">
                                <div class="user-cell">
                                    <Avatar
                                        v-if="data.avatar"
                                        :image="data.avatar"
                                        shape="circle"
                                        size="normal"
                                    />
                                    <Avatar
                                        v-else
                                        :label="getInitials(data.name)"
                                        shape="circle"
                                        size="normal"
                                        class="user-avatar"
                                    />
                                    <div class="user-info">
                                        <span class="user-name">{{ data.name }}</span>
                                        <span class="user-email">{{ data.email }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column header="Role">
                            <template #body="{ data }">
                                <Tag :severity="getRoleSeverity(data.role)" :value="data.role_label" />
                            </template>
                        </Column>

                        <Column header="Provider" style="min-width: 150px">
                            <template #body="{ data }">
                                <span v-if="data.provider" class="provider-name">
                                    {{ data.provider.business_name }}
                                </span>
                                <span v-else class="no-provider">-</span>
                            </template>
                        </Column>

                        <Column header="Status">
                            <template #body="{ data }">
                                <Tag
                                    :severity="data.is_active ? 'success' : 'danger'"
                                    :value="data.is_active ? 'Active' : 'Inactive'"
                                />
                            </template>
                        </Column>

                        <Column header="Last Login">
                            <template #body="{ data }">
                                <span v-if="data.last_login_at" class="last-login">
                                    {{ data.last_login_at }}
                                </span>
                                <span v-else class="never-logged">Never</span>
                            </template>
                        </Column>

                        <Column header="Joined" field="created_at" />

                        <Column header="Actions" style="width: 120px">
                            <template #body="{ data }">
                                <div class="action-buttons">
                                    <Link :href="route('admin.users.show', data.uuid)">
                                        <Button
                                            icon="pi pi-eye"
                                            text
                                            rounded
                                            size="small"
                                            v-tooltip.top="'View'"
                                        />
                                    </Link>
                                    <Button
                                        :icon="data.is_active ? 'pi pi-ban' : 'pi pi-check'"
                                        :severity="data.is_active ? 'warning' : 'success'"
                                        text
                                        rounded
                                        size="small"
                                        @click="toggleUserStatus(data)"
                                        v-tooltip.top="data.is_active ? 'Deactivate' : 'Activate'"
                                    />
                                    <Button
                                        icon="pi pi-trash"
                                        severity="danger"
                                        text
                                        rounded
                                        size="small"
                                        @click="deleteUser(data)"
                                        v-tooltip.top="'Delete'"
                                    />
                                </div>
                            </template>
                        </Column>
                    </DataTable>

                    <!-- Pagination -->
                    <div class="pagination" v-if="users.last_page > 1">
                        <Link
                            v-for="link in users.links"
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
.users-page {
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

.user-cell {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    background-color: var(--p-primary-color);
    color: white;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 500;
    color: var(--p-surface-900);
}

.user-email {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

.provider-name {
    color: var(--p-surface-700);
}

.no-provider,
.never-logged {
    color: var(--p-surface-400);
    font-style: italic;
}

.last-login {
    font-size: 0.875rem;
    color: var(--p-surface-600);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
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

    .search-box {
        width: 100%;
    }

    .filter-select {
        width: 100%;
    }
}
</style>
