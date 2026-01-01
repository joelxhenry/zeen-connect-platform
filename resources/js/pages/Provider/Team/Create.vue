<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SettingsLayout from '@/components/layout/SettingsLayout.vue';
import ConsoleFormCard from '@/components/console/ConsoleFormCard.vue';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import RadioButton from 'primevue/radiobutton';
import Message from 'primevue/message';

interface Permission {
    label: string;
    description: string;
}

interface PermissionPreset {
    label: string;
    description: string;
    permissions: string[];
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
    permissions: Record<string, Permission>;
    permissionsGrouped: Record<string, { label: string; permissions: string[] }>;
    presets: Record<string, PermissionPreset>;
    defaultPermissions: string[];
    teamInfo: TeamInfo;
}>();

const mode = ref<'invite' | 'create'>('invite');
const selectedPreset = ref<string | null>(null);

const form = useForm({
    email: '',
    name: '',
    title: '',
    permissions: [...props.defaultPermissions],
});

const hasExtraCharge = computed(() => {
    return props.teamInfo.would_exceed_free_slots;
});

const applyPreset = (presetKey: string) => {
    selectedPreset.value = presetKey;
    const preset = props.presets[presetKey];
    if (preset) {
        form.permissions = [...preset.permissions];
    }
};

const togglePermission = (permission: string) => {
    selectedPreset.value = null; // Clear preset when manually changing
    const index = form.permissions.indexOf(permission);
    if (index === -1) {
        form.permissions.push(permission);
    } else {
        form.permissions.splice(index, 1);
    }
};

const submit = () => {
    form.post(resolveUrl(provider.team.store.url()), {
        onSuccess: () => {
            form.reset();
        },
    });
};

const cancel = () => {
    router.visit(resolveUrl(provider.team.index.url()));
};
</script>

<template>
    <SettingsLayout title="Add Team Member">
        <div class="create-page">
            <!-- Extra Charge Warning -->
            <Message v-if="hasExtraCharge" severity="warn" :closable="false">
                <div class="charge-warning">
                    <strong>Additional charges will apply</strong>
                    <p>
                        You've used all {{ teamInfo.free_slots }} free team member slots.
                        Adding this member will cost an additional ${{ (teamInfo.fee_per_extra / 100).toFixed(2) }}/month.
                    </p>
                </div>
            </Message>

            <!-- Mode Selection -->
            <ConsoleFormCard title="How would you like to add a team member?">
                <div class="mode-options">
                    <div
                        class="mode-option"
                        :class="{ selected: mode === 'invite' }"
                        @click="mode = 'invite'"
                    >
                        <RadioButton v-model="mode" inputId="mode_invite" value="invite" />
                        <div class="mode-content">
                            <label for="mode_invite" class="mode-label">Send Email Invitation</label>
                            <p class="mode-description">
                                Send an invitation email. They'll create their own password when they accept.
                            </p>
                        </div>
                    </div>

                    <div
                        class="mode-option"
                        :class="{ selected: mode === 'create' }"
                        @click="mode = 'create'"
                    >
                        <RadioButton v-model="mode" inputId="mode_create" value="create" />
                        <div class="mode-content">
                            <label for="mode_create" class="mode-label">Create Account Directly</label>
                            <p class="mode-description">
                                Create their account now. You'll receive a temporary password to share with them.
                            </p>
                        </div>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Member Details -->
            <ConsoleFormCard title="Member Details">
                <div class="form-grid">
                    <div v-if="mode === 'create'" class="form-field">
                        <label for="name" class="form-label">Full Name *</label>
                        <InputText
                            id="name"
                            v-model="form.name"
                            :class="{ 'p-invalid': form.errors.name }"
                            placeholder="Enter their full name"
                            class="w-full"
                        />
                        <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                    </div>

                    <div v-if="mode === 'create'" class="form-field">
                        <label for="title" class="form-label">Title / Role</label>
                        <InputText
                            id="title"
                            v-model="form.title"
                            :class="{ 'p-invalid': form.errors.title }"
                            placeholder="e.g., Senior Stylist"
                            class="w-full"
                        />
                        <small v-if="form.errors.title" class="p-error">{{ form.errors.title }}</small>
                    </div>

                    <div class="form-field">
                        <label for="email" class="form-label">Email Address *</label>
                        <InputText
                            id="email"
                            v-model="form.email"
                            type="email"
                            :class="{ 'p-invalid': form.errors.email }"
                            placeholder="Enter their email address"
                            class="w-full"
                        />
                        <small v-if="form.errors.email" class="p-error">{{ form.errors.email }}</small>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Permission Presets -->
            <ConsoleFormCard title="Quick Setup">
                <p class="section-description">
                    Choose a role to quickly set appropriate permissions, or customize below.
                </p>
                <div class="preset-grid">
                    <div
                        v-for="(preset, key) in presets"
                        :key="key"
                        class="preset-card"
                        :class="{ selected: selectedPreset === key }"
                        @click="applyPreset(key)"
                    >
                        <div class="preset-header">
                            <RadioButton
                                v-model="selectedPreset"
                                :inputId="'preset_' + key"
                                :value="key"
                            />
                            <label :for="'preset_' + key" class="preset-label">{{ preset.label }}</label>
                        </div>
                        <p class="preset-description">{{ preset.description }}</p>
                    </div>
                </div>
            </ConsoleFormCard>

            <!-- Custom Permissions -->
            <ConsoleFormCard title="Permissions">
                <p class="section-description">
                    Fine-tune what this team member can access and do.
                </p>

                <div class="permissions-grid">
                    <div
                        v-for="(group, groupKey) in permissionsGrouped"
                        :key="groupKey"
                        class="permission-group"
                    >
                        <h4 class="group-title">{{ group.label }}</h4>
                        <div class="permission-list">
                            <div
                                v-for="permKey in group.permissions"
                                :key="permKey"
                                class="permission-item"
                                @click="togglePermission(permKey)"
                            >
                                <Checkbox
                                    :modelValue="form.permissions.includes(permKey)"
                                    @update:modelValue="togglePermission(permKey)"
                                    :inputId="'perm_' + permKey"
                                    :binary="true"
                                />
                                <div class="permission-content">
                                    <label :for="'perm_' + permKey" class="permission-label">
                                        {{ permissions[permKey]?.label }}
                                    </label>
                                    <span class="permission-description">
                                        {{ permissions[permKey]?.description }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <small v-if="form.errors.permissions" class="p-error">{{ form.errors.permissions }}</small>
            </ConsoleFormCard>

            <!-- Actions -->
            <div class="form-actions">
                <Button
                    label="Cancel"
                    severity="secondary"
                    text
                    @click="cancel"
                />
                <Button
                    :label="mode === 'invite' ? 'Send Invitation' : 'Create Account'"
                    :icon="mode === 'invite' ? 'pi pi-send' : 'pi pi-user-plus'"
                    :loading="form.processing"
                    @click="submit"
                />
            </div>
        </div>
    </SettingsLayout>
</template>

<style scoped>
.create-page {
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.charge-warning p {
    margin: 0.25rem 0 0;
    font-size: 0.875rem;
    opacity: 0.9;
}

.mode-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.mode-option {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.mode-option:hover {
    border-color: var(--color-slate-300, #cbd5e1);
}

.mode-option.selected {
    border-color: #106B4F;
    background: rgba(16, 107, 79, 0.04);
}

.mode-content {
    flex: 1;
}

.mode-label {
    display: block;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--color-slate-900, #0f172a);
    cursor: pointer;
}

.mode-description {
    margin: 0.25rem 0 0;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.5;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

@media (min-width: 640px) {
    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.section-description {
    margin: 0 0 1.25rem;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.5;
}

.preset-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0.75rem;
}

@media (min-width: 640px) {
    .preset-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 768px) {
    .preset-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

.preset-card {
    padding: 1rem;
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.preset-card:hover {
    border-color: var(--color-slate-300, #cbd5e1);
}

.preset-card.selected {
    border-color: #106B4F;
    background: rgba(16, 107, 79, 0.04);
}

.preset-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}

.preset-label {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
    cursor: pointer;
}

.preset-description {
    margin: 0;
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.5;
}

.permissions-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.permission-group {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.group-title {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--color-slate-600, #475569);
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.permission-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.permission-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background 0.15s ease;
}

.permission-item:hover {
    background: var(--color-slate-100, #f1f5f9);
}

.permission-content {
    flex: 1;
}

.permission-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-900, #0f172a);
    cursor: pointer;
}

.permission-description {
    display: block;
    margin-top: 0.125rem;
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    padding-top: 0.5rem;
}

.w-full {
    width: 100%;
}
</style>
