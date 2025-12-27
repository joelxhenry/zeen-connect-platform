<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleButton,
} from '@/components/console';
import Button from 'primevue/button';
import Avatar from 'primevue/avatar';
import Checkbox from 'primevue/checkbox';
import Tag from 'primevue/tag';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import type { TeamMember, TeamPermission, TeamPreset } from '@/types/models';

interface MemberData {
    id: number;
    uuid: string;
    email: string;
    name: string;
    avatar: string | null;
    permissions: string[];
    status: string;
    status_label: string;
    invited_at: string | null;
    accepted_at: string | null;
    is_pending: boolean;
    is_expired: boolean;
}

interface Props {
    member: MemberData;
    permissions: Record<string, TeamPermission>;
    permissionsGrouped: Record<string, Record<string, TeamPermission>>;
    presets: Record<string, TeamPreset>;
}

const props = defineProps<Props>();
const confirm = useConfirm();
const toast = useToast();

const form = useForm({
    permissions: [...props.member.permissions],
});

const selectedPreset = ref<string | null>(null);

const applyPreset = (preset: string) => {
    if (props.presets[preset]) {
        form.permissions = [...props.presets[preset].permissions];
        selectedPreset.value = preset;
    }
};

const isPresetActive = computed(() => {
    for (const [key, preset] of Object.entries(props.presets)) {
        if (
            form.permissions.length === preset.permissions.length &&
            form.permissions.every((p) => preset.permissions.includes(p))
        ) {
            return key;
        }
    }
    return null;
});

const togglePermission = (permission: string) => {
    const index = form.permissions.indexOf(permission);
    if (index > -1) {
        form.permissions.splice(index, 1);
    } else {
        form.permissions.push(permission);
    }
    selectedPreset.value = null;
};

const submit = () => {
    form.put(`/team/${props.member.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Updated',
                detail: 'Permissions updated successfully',
                life: 3000,
            });
        },
    });
};

const suspendMember = () => {
    confirm.require({
        message: `Are you sure you want to suspend ${props.member.name}? They will no longer be able to access your provider console.`,
        header: 'Suspend Team Member',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-orange-500 !border-orange-500',
        accept: () => {
            router.post(`/team/${props.member.id}/suspend`, {}, {
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

const reactivateMember = () => {
    router.post(`/team/${props.member.id}/reactivate`, {}, {
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

const deleteMember = () => {
    confirm.require({
        message: `Are you sure you want to remove ${props.member.name} from your team? This action cannot be undone.`,
        header: 'Remove Team Member',
        icon: 'pi pi-exclamation-triangle',
        acceptClass: '!bg-red-500 !border-red-500',
        accept: () => {
            router.delete(`/team/${props.member.id}`, {
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
    <ConsoleLayout title="Edit Team Member">
        <ConfirmDialog />

        <div class="w-full max-w-2xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Edit Team Member"
                subtitle="Update permissions for this team member"
                back-href="/team"
            />

            <ConsoleFormCard>
                <!-- Member Info -->
                <div class="flex items-start gap-4 mb-6 pb-6 border-b border-gray-100">
                    <Avatar
                        :image="member.avatar || undefined"
                        :label="member.name?.[0]?.toUpperCase()"
                        size="xlarge"
                        shape="circle"
                        class="shrink-0 !bg-[#106B4F] !text-white"
                    />
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <h2 class="font-semibold text-[#0D1F1B] m-0 text-lg">{{ member.name }}</h2>
                            <Tag
                                :value="member.status_label"
                                :severity="getStatusSeverity(member.status)"
                            />
                        </div>
                        <p class="text-gray-500 m-0 mb-2">{{ member.email }}</p>
                        <div class="flex flex-wrap gap-4 text-xs text-gray-400">
                            <span v-if="member.invited_at">
                                <i class="pi pi-send mr-1"></i>
                                Invited {{ member.invited_at }}
                            </span>
                            <span v-if="member.accepted_at">
                                <i class="pi pi-check mr-1"></i>
                                Joined {{ member.accepted_at }}
                            </span>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Presets -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Quick Presets</label>
                        <div class="flex flex-wrap gap-2">
                            <Button
                                v-for="(preset, key) in presets"
                                :key="key"
                                :label="preset.label"
                                size="small"
                                :severity="isPresetActive === key ? 'success' : 'secondary'"
                                :outlined="isPresetActive !== key"
                                @click="applyPreset(key)"
                            />
                        </div>
                    </div>

                    <!-- Permissions -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Permissions</label>
                        <small v-if="form.errors.permissions" class="text-red-500 block mb-2">{{ form.errors.permissions }}</small>

                        <div class="space-y-4">
                            <div v-for="(groupPerms, groupName) in permissionsGrouped" :key="groupName">
                                <h4 class="text-sm font-medium text-gray-600 mb-2">{{ groupName }}</h4>
                                <div class="space-y-2 pl-2">
                                    <div
                                        v-for="perm in groupPerms"
                                        :key="perm.key"
                                        class="flex items-start gap-2"
                                    >
                                        <Checkbox
                                            :inputId="perm.key"
                                            :modelValue="form.permissions.includes(perm.key)"
                                            @update:modelValue="togglePermission(perm.key)"
                                            :binary="true"
                                        />
                                        <div class="flex-1">
                                            <label :for="perm.key" class="text-sm font-medium text-gray-700 cursor-pointer">
                                                {{ perm.label }}
                                            </label>
                                            <p class="text-xs text-gray-500 m-0">{{ perm.description }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-wrap justify-between gap-3 pt-4 border-t border-gray-100">
                        <div class="flex gap-2">
                            <ConsoleButton
                                v-if="member.status === 'active'"
                                label="Suspend"
                                icon="pi pi-ban"
                                variant="secondary"
                                outlined
                                size="small"
                                @click="suspendMember"
                            />
                            <ConsoleButton
                                v-if="member.status === 'suspended'"
                                label="Reactivate"
                                icon="pi pi-check"
                                variant="success"
                                outlined
                                size="small"
                                @click="reactivateMember"
                            />
                            <ConsoleButton
                                label="Remove"
                                icon="pi pi-trash"
                                variant="danger"
                                outlined
                                size="small"
                                @click="deleteMember"
                            />
                        </div>

                        <div class="flex gap-3">
                            <ConsoleButton
                                label="Cancel"
                                variant="secondary"
                                href="/team"
                            />
                            <ConsoleButton
                                label="Save Changes"
                                icon="pi pi-check"
                                type="submit"
                                :loading="form.processing"
                            />
                        </div>
                    </div>
                </form>
            </ConsoleFormCard>
        </div>
    </ConsoleLayout>
</template>
