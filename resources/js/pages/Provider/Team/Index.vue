<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import SettingsLayout from '@/components/layout/SettingsLayout.vue';
import ConsoleEmptyState from '@/components/console/ConsoleEmptyState.vue';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import Menu from 'primevue/menu';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';

interface TeamMember {
    id: number;
    uuid: string;
    email: string;
    name: string;
    title: string | null;
    avatar: string | null;
    permissions: string[];
    permissions_summary: string;
    status: string;
    status_label: string;
    status_color: string;
    invited_at: string | null;
    accepted_at: string | null;
    is_expired: boolean;
}

interface TeamInfo {
    tier: string;
    tier_label: string;
    supports_team: boolean;
    free_slots: number;
    active_count: number;
    extra_count: number;
    fee_per_extra: number;
    total_extra_fee: number;
    would_exceed_free_slots: boolean;
}

const props = defineProps<{
    teamMembers: TeamMember[];
    teamInfo: TeamInfo;
    permissions: Record<string, { label: string; description: string }>;
    permissionsGrouped: Record<string, { label: string; permissions: string[] }>;
}>();

const confirm = useConfirm();
const menuRef = ref<Record<string, InstanceType<typeof Menu>>>({});
const selectedMember = ref<TeamMember | null>(null);

const getStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'info' | 'secondary' => {
    switch (status) {
        case 'active': return 'success';
        case 'pending': return 'warn';
        case 'suspended': return 'danger';
        default: return 'secondary';
    }
};

const getMemberMenuItems = (member: TeamMember) => {
    const items = [];

    // Edit permissions
    items.push({
        label: 'Edit Permissions',
        icon: 'pi pi-pencil',
        command: () => router.visit(resolveUrl(provider.team.edit.url({ member: member.id.toString() }))),
    });

    // Edit schedule (only for active members)
    if (member.status === 'active') {
        items.push({
            label: 'Edit Schedule',
            icon: 'pi pi-clock',
            command: () => router.visit(resolveUrl(provider.team.availability.edit.url({ member: member.id.toString() }))),
        });
    }

    // Pending member actions
    if (member.status === 'pending') {
        items.push({
            label: 'Resend Invitation',
            icon: 'pi pi-send',
            command: () => resendInvitation(member),
        });
    }

    // Active member actions
    if (member.status === 'active') {
        items.push({
            label: 'Suspend',
            icon: 'pi pi-ban',
            command: () => confirmSuspend(member),
        });
    }

    // Suspended member actions
    if (member.status === 'suspended') {
        items.push({
            label: 'Reactivate',
            icon: 'pi pi-check-circle',
            command: () => reactivateMember(member),
        });
    }

    // Delete (always available)
    items.push({
        separator: true,
    });
    items.push({
        label: 'Remove',
        icon: 'pi pi-trash',
        class: 'text-red-600',
        command: () => confirmDelete(member),
    });

    return items;
};

const toggleMenu = (event: Event, member: TeamMember) => {
    selectedMember.value = member;
    menuRef.value[member.id]?.toggle(event);
};

const resendInvitation = (member: TeamMember) => {
    router.post(resolveUrl(provider.team.resend.url({ member: member.id.toString() })));
};

const confirmSuspend = (member: TeamMember) => {
    confirm.require({
        message: `Are you sure you want to suspend ${member.name}? They will no longer be able to access your account.`,
        header: 'Suspend Team Member',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-text',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Suspend',
        rejectLabel: 'Cancel',
        accept: () => {
            router.post(resolveUrl(provider.team.suspend.url({ member: member.id.toString() })));
        },
    });
};

const reactivateMember = (member: TeamMember) => {
    router.post(resolveUrl(provider.team.reactivate.url({ member: member.id.toString() })));
};

const confirmDelete = (member: TeamMember) => {
    confirm.require({
        message: `Are you sure you want to remove ${member.name}? This action cannot be undone.`,
        header: 'Remove Team Member',
        icon: 'pi pi-exclamation-triangle',
        rejectClass: 'p-button-secondary p-button-text',
        acceptClass: 'p-button-danger',
        acceptLabel: 'Remove',
        rejectLabel: 'Cancel',
        accept: () => {
            router.delete(resolveUrl(provider.team.destroy.url({ member: member.id.toString() })));
        },
    });
};
</script>

<template>
    <SettingsLayout title="Team">
        <template #header-actions>
            <Button
                label="Add Team Member"
                icon="pi pi-plus"
                @click="router.visit(resolveUrl(provider.team.create.url()))"
            />
        </template>

        <div class="team-page">
            <!-- Team Info Banner -->
            <div class="team-info-banner">
                <div class="info-item">
                    <span class="info-label">Plan</span>
                    <span class="info-value">{{ teamInfo.tier_label }}</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Team Members</span>
                    <span class="info-value">
                        {{ teamInfo.active_count }}
                        <span v-if="teamInfo.free_slots !== -1">/ {{ teamInfo.free_slots }} free</span>
                        <span v-else>(unlimited)</span>
                    </span>
                </div>
                <div v-if="teamInfo.extra_count > 0" class="info-item">
                    <span class="info-label">Extra Members</span>
                    <span class="info-value warning">
                        {{ teamInfo.extra_count }} (+${{ (teamInfo.total_extra_fee / 100).toFixed(2) }}/mo)
                    </span>
                </div>
            </div>

            <!-- Empty State -->
            <ConsoleEmptyState
                v-if="teamMembers.length === 0"
                icon="pi pi-users"
                title="No team members yet"
                description="Invite team members to help manage your business. They can handle bookings, services, and more based on the permissions you assign."
            >
                <template #actions>
                    <Button
                        label="Add Your First Team Member"
                        icon="pi pi-plus"
                        @click="router.visit(resolveUrl(provider.team.create.url()))"
                    />
                </template>
            </ConsoleEmptyState>

            <!-- Team Members List -->
            <div v-else class="team-list">
                <div
                    v-for="member in teamMembers"
                    :key="member.id"
                    class="team-member-card"
                    :class="{ expired: member.is_expired }"
                >
                    <div class="member-main">
                        <Avatar
                            :image="member.avatar || undefined"
                            :label="member.name.charAt(0).toUpperCase()"
                            shape="circle"
                            size="large"
                            class="member-avatar"
                        />
                        <div class="member-info">
                            <div class="member-name-row">
                                <span class="member-name">{{ member.name }}</span>
                                <Tag
                                    :value="member.is_expired ? 'Expired' : member.status_label"
                                    :severity="member.is_expired ? 'danger' : getStatusSeverity(member.status)"
                                    class="status-tag"
                                />
                            </div>
                            <span v-if="member.title" class="member-title">{{ member.title }}</span>
                            <span class="member-email">{{ member.email }}</span>
                            <span class="member-permissions">{{ member.permissions_summary }}</span>
                        </div>
                    </div>

                    <div class="member-actions">
                        <div v-if="member.status === 'pending'" class="pending-info">
                            <span class="pending-label">Invited {{ member.invited_at }}</span>
                        </div>
                        <Button
                            icon="pi pi-ellipsis-v"
                            text
                            rounded
                            @click="toggleMenu($event, member)"
                            aria-label="Member actions"
                        />
                        <Menu
                            :ref="(el: any) => menuRef[member.id] = el"
                            :model="getMemberMenuItems(member)"
                            popup
                        />
                    </div>
                </div>
            </div>
        </div>

        <ConfirmDialog />
    </SettingsLayout>
</template>

<style scoped>
.team-page {
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.team-info-banner {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding: 1rem 1.25rem;
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.info-label {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--color-slate-500, #64748b);
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.info-value {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.info-value.warning {
    color: #f59e0b;
}

.team-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.team-member-card {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
    transition: all 0.15s ease;
}

.team-member-card:hover {
    border-color: var(--color-slate-300, #cbd5e1);
}

.team-member-card.expired {
    opacity: 0.7;
    background: var(--color-slate-50, #f8fafc);
}

.member-main {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}

.member-avatar {
    flex-shrink: 0;
    background: #106B4F;
    color: white;
}

.member-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    min-width: 0;
}

.member-name-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.member-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.status-tag {
    font-size: 0.625rem;
}

.member-title {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
}

.member-email {
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.member-permissions {
    font-size: 0.75rem;
    color: var(--color-slate-400, #94a3b8);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.member-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.pending-info {
    display: none;
}

@media (min-width: 640px) {
    .pending-info {
        display: block;
    }

    .pending-label {
        font-size: 0.75rem;
        color: var(--color-slate-400, #94a3b8);
    }
}
</style>
