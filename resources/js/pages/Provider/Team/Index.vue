<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleEmptyState,
    ConsoleAlertBanner,
    ConsoleButton,
    ConsoleDataCard,
} from '@/components/console';
import AppLink from '@/components/common/AppLink.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Avatar from 'primevue/avatar';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import type { TeamMember, TeamPermission, TeamInfo } from '@/types/models';

interface Props {
    teamMembers: TeamMember[];
    teamInfo: TeamInfo;
    permissions: Record<string, TeamPermission>;
    permissionsGrouped: Record<string, Record<string, TeamPermission>>;
}

const props = defineProps<Props>();
const confirm = useConfirm();
const toast = useToast();

const deleteMember = (member: TeamMember) => {
    confirm.require({
        message: `Are you sure you want to remove ${member.name} from your team?`,
        header: 'Remove Team Member',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-500 !border-red-500',
        accept: () => {
            router.delete(`/team/${member.id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Removed',
                        detail: 'Team member removed successfully',
                        life: 3000,
                    });
                },
            });
        },
    });
};

const resendInvite = (member: TeamMember) => {
    router.post(`/team/${member.id}/resend`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Sent',
                detail: 'Invitation resent successfully',
                life: 3000,
            });
        },
    });
};

const suspendMember = (member: TeamMember) => {
    confirm.require({
        message: `Are you sure you want to suspend ${member.name}? They will no longer be able to access your provider console.`,
        header: 'Suspend Team Member',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-orange-500 !border-orange-500',
        accept: () => {
            router.post(`/team/${member.id}/suspend`, {}, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: 'success',
                        summary: 'Suspended',
                        detail: 'Team member suspended successfully',
                        life: 3000,
                    });
                },
            });
        },
    });
};

const reactivateMember = (member: TeamMember) => {
    router.post(`/team/${member.id}/reactivate`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Reactivated',
                detail: 'Team member reactivated successfully',
                life: 3000,
            });
        },
    });
};

const getStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'info' | 'secondary' => {
    switch (status) {
        case 'active':
            return 'success';
        case 'pending':
            return 'warn';
        case 'suspended':
            return 'danger';
        default:
            return 'secondary';
    }
};
</script>

<template>
    <ConsoleLayout title="Team">
        <ConfirmDialog />

        <div class="w-full max-w-7xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Team Members"
                subtitle="Manage your team and their permissions"
            >
                <template #actions>
                    <ConsoleButton
                        label="Invite Member"
                        icon="pi pi-user-plus"
                        href="/team/invite"
                    />
                </template>
            </ConsolePageHeader>

            <!-- Team Info Card -->
            <ConsoleFormCard class="mb-6">
                <div class="flex flex-wrap items-center gap-4 lg:gap-8">
                    <div>
                        <span class="text-gray-500 text-sm">Plan</span>
                        <p class="font-semibold text-[#0D1F1B] m-0">{{ teamInfo.tier_label }}</p>
                    </div>
                    <div>
                        <span class="text-gray-500 text-sm">Active Members</span>
                        <p class="font-semibold text-[#0D1F1B] m-0">
                            {{ teamInfo.active_count }}
                            <span v-if="teamInfo.free_slots > 0 && teamInfo.free_slots < 1000" class="text-gray-400 font-normal">
                                / {{ teamInfo.free_slots }} free
                            </span>
                            <span v-else-if="teamInfo.free_slots === -1" class="text-gray-400 font-normal">
                                (unlimited)
                            </span>
                        </p>
                    </div>
                    <div v-if="teamInfo.extra_count > 0">
                        <span class="text-gray-500 text-sm">Extra Members</span>
                        <p class="font-semibold text-orange-600 m-0">
                            {{ teamInfo.extra_count }} (+₦{{ teamInfo.total_extra_fee.toLocaleString() }}/mo)
                        </p>
                    </div>
                </div>

                <ConsoleAlertBanner
                    v-if="teamInfo.would_exceed_free_slots"
                    variant="warning"
                    class="mt-4"
                >
                    Adding more members will incur an additional ₦{{ teamInfo.fee_per_extra.toLocaleString() }}/month per member.
                </ConsoleAlertBanner>
            </ConsoleFormCard>

            <!-- Empty State -->
            <div v-if="teamMembers.length === 0" class="bg-white rounded-xl shadow-sm">
                <ConsoleEmptyState
                    icon="pi pi-users"
                    title="No team members yet"
                    description="Invite team members to help manage bookings, services, and more."
                    action-label="Invite Your First Team Member"
                    action-href="/team/invite"
                    action-icon="pi pi-user-plus"
                />
            </div>

            <!-- Team Members List -->
            <div v-else class="space-y-4">
                <ConsoleDataCard
                    v-for="member in teamMembers"
                    :key="member.id"
                >
                    <div class="flex items-start gap-4">
                        <Avatar
                            :image="member.avatar || undefined"
                            :label="member.name?.[0]?.toUpperCase()"
                            size="large"
                            shape="circle"
                            class="shrink-0 !bg-[#106B4F] !text-white"
                        />

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <h3 class="font-semibold text-[#0D1F1B] m-0">{{ member.name }}</h3>
                                <Tag
                                    :value="member.status_label"
                                    :severity="getStatusSeverity(member.status)"
                                    class="!text-xs"
                                />
                                <Tag
                                    v-if="member.is_expired"
                                    value="Expired"
                                    severity="danger"
                                    class="!text-xs"
                                />
                            </div>
                            <p class="text-sm text-gray-500 m-0 mb-2">{{ member.email }}</p>
                            <p class="text-sm text-gray-400 m-0">{{ member.permissions_summary }}</p>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <AppLink v-if="member.status !== 'pending'" :href="`/team/${member.id}/edit`">
                                <Button icon="pi pi-pencil" size="small" severity="secondary" outlined v-tooltip="'Edit Permissions'" />
                            </AppLink>

                            <Button
                                v-if="member.status === 'pending' && !member.is_expired"
                                icon="pi pi-send"
                                size="small"
                                severity="info"
                                outlined
                                v-tooltip="'Resend Invite'"
                                @click="resendInvite(member)"
                            />

                            <Button
                                v-if="member.status === 'active'"
                                icon="pi pi-ban"
                                size="small"
                                severity="warn"
                                outlined
                                v-tooltip="'Suspend'"
                                @click="suspendMember(member)"
                            />

                            <Button
                                v-if="member.status === 'suspended'"
                                icon="pi pi-check"
                                size="small"
                                severity="success"
                                outlined
                                v-tooltip="'Reactivate'"
                                @click="reactivateMember(member)"
                            />

                            <Button
                                icon="pi pi-trash"
                                size="small"
                                severity="danger"
                                outlined
                                v-tooltip="'Remove'"
                                @click="deleteMember(member)"
                            />
                        </div>
                    </div>

                    <template #footer>
                        <div v-if="member.invited_at || member.accepted_at" class="flex flex-wrap gap-4 text-xs text-gray-400">
                            <span v-if="member.invited_at">
                                <i class="pi pi-send mr-1"></i>
                                Invited {{ member.invited_at }}
                            </span>
                            <span v-if="member.accepted_at">
                                <i class="pi pi-check mr-1"></i>
                                Joined {{ member.accepted_at }}
                            </span>
                        </div>
                    </template>
                </ConsoleDataCard>
            </div>
        </div>
    </ConsoleLayout>
</template>
