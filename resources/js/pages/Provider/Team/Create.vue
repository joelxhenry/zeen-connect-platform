<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleFormSection,
    ConsoleAlertBanner,
    ConsoleButton,
} from '@/components/console';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import SelectButton from 'primevue/selectbutton';
import type { TeamPermission, TeamPreset, TeamInfo } from '@/types/models';

interface Props {
    permissions: Record<string, TeamPermission>;
    permissionsGrouped: Record<string, Record<string, TeamPermission>>;
    presets: Record<string, TeamPreset>;
    defaultPermissions: string[];
    teamInfo: TeamInfo;
}

const props = defineProps<Props>();

const inviteMode = ref<'invite' | 'direct'>('invite');

const form = useForm({
    email: '',
    name: '',
    permissions: [...props.defaultPermissions],
    send_credentials: true,
});

const modeOptions = [
    { label: 'Send Invite', value: 'invite' },
    { label: 'Create Directly', value: 'direct' },
];

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
    form.post('/team', {
        preserveScroll: true,
    });
};
</script>

<template>
    <ConsoleLayout title="Invite Team Member">
        <div class="w-full max-w-2xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Invite Team Member"
                subtitle="Add someone to help manage your business"
                back-href="/team"
            />

            <!-- Extra charge warning -->
            <ConsoleAlertBanner
                v-if="teamInfo.would_exceed_free_slots"
                variant="warning"
            >
                You've used all your free team member slots. Adding this member will cost an additional
                ₦{{ teamInfo.fee_per_extra.toLocaleString() }}/month.
            </ConsoleAlertBanner>

            <ConsoleFormCard>
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Invite Mode Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">How would you like to add them?</label>
                        <SelectButton
                            v-model="inviteMode"
                            :options="modeOptions"
                            optionLabel="label"
                            optionValue="value"
                            :allowEmpty="false"
                        />
                    </div>

                    <!-- Email & Name -->
                    <ConsoleFormSection :columns="2">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.email }"
                                placeholder="colleague@example.com"
                            />
                            <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
                        </div>
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                                Name {{ inviteMode === 'direct' ? '*' : '(optional)' }}
                            </label>
                            <InputText
                                id="name"
                                v-model="form.name"
                                class="w-full"
                                :class="{ 'p-invalid': form.errors.name }"
                                placeholder="John Doe"
                            />
                            <small v-if="form.errors.name" class="text-red-500">{{ form.errors.name }}</small>
                        </div>
                    </ConsoleFormSection>

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

                    <!-- Send Credentials (for direct mode) -->
                    <ConsoleFormSection v-if="inviteMode === 'direct'" highlighted>
                        <div class="flex items-start gap-2">
                            <Checkbox
                                inputId="send_credentials"
                                v-model="form.send_credentials"
                                :binary="true"
                            />
                            <div class="flex-1">
                                <label for="send_credentials" class="text-sm font-medium text-gray-700 cursor-pointer">
                                    Send login credentials via email
                                </label>
                                <p class="text-xs text-gray-500 m-0">
                                    A temporary password will be generated and sent to the team member
                                </p>
                            </div>
                        </div>
                    </ConsoleFormSection>

                    <!-- Submit -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <ConsoleButton
                            label="Cancel"
                            variant="secondary"
                            href="/team"
                        />
                        <ConsoleButton
                            :label="inviteMode === 'invite' ? 'Send Invitation' : 'Add Team Member'"
                            :icon="inviteMode === 'invite' ? 'pi pi-send' : 'pi pi-user-plus'"
                            type="submit"
                            :loading="form.processing"
                        />
                    </div>
                </form>
            </ConsoleFormCard>
        </div>
    </ConsoleLayout>
</template>
