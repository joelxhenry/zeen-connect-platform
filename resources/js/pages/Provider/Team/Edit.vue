<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import ConsoleFormCard from '@/components/console/ConsoleFormCard.vue';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import RadioButton from 'primevue/radiobutton';
import Tag from 'primevue/tag';

interface Permission {
    label: string;
    description: string;
}

interface PermissionPreset {
    label: string;
    description: string;
    permissions: string[];
}

interface TeamMember {
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

const props = defineProps<{
    member: TeamMember;
    permissions: Record<string, Permission>;
    permissionsGrouped: Record<string, { label: string; permissions: string[] }>;
    presets: Record<string, PermissionPreset>;
}>();

const selectedPreset = ref<string | null>(null);

const form = useForm({
    permissions: [...props.member.permissions],
});

const isDirty = computed(() => form.isDirty);

const getStatusSeverity = (status: string): 'success' | 'warn' | 'danger' | 'info' | 'secondary' => {
    switch (status) {
        case 'active': return 'success';
        case 'pending': return 'warn';
        case 'suspended': return 'danger';
        default: return 'secondary';
    }
};

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

const save = () => {
    form.put(resolveUrl(provider.team.update.url({ member: props.member.id.toString() })), {
        preserveScroll: true,
        onSuccess: () => {
            form.defaults({
                permissions: [...form.permissions],
            });
            form.reset();
        },
    });
};

const cancel = () => {
    router.visit(resolveUrl(provider.team.index.url()));
};

// Warn on navigation with unsaved changes
const beforeUnloadHandler = (e: BeforeUnloadEvent) => {
    if (isDirty.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

onMounted(() => {
    window.addEventListener('beforeunload', beforeUnloadHandler);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', beforeUnloadHandler);
});
</script>

<template>
    <ConsoleLayout :title="'Edit ' + member.name">
        <div class="edit-page">
            <!-- Member Info Card -->
            <ConsoleFormCard>
                <div class="member-header">
                    <Avatar
                        :image="member.avatar || undefined"
                        :label="member.name.charAt(0).toUpperCase()"
                        shape="circle"
                        size="xlarge"
                        class="member-avatar"
                    />
                    <div class="member-details">
                        <div class="member-name-row">
                            <h2 class="member-name">{{ member.name }}</h2>
                            <Tag
                                :value="member.is_expired ? 'Expired' : member.status_label"
                                :severity="member.is_expired ? 'danger' : getStatusSeverity(member.status)"
                            />
                        </div>
                        <p class="member-email">{{ member.email }}</p>
                        <div class="member-dates">
                            <span v-if="member.invited_at" class="date-item">
                                <i class="pi pi-send"></i>
                                Invited {{ member.invited_at }}
                            </span>
                            <span v-if="member.accepted_at" class="date-item">
                                <i class="pi pi-check-circle"></i>
                                Accepted {{ member.accepted_at }}
                            </span>
                        </div>
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

            <!-- Floating Save Button -->
            <Transition name="slide-up">
                <div v-if="isDirty" class="floating-save">
                    <div class="save-bar">
                        <span class="save-text">You have unsaved changes</span>
                        <div class="save-actions">
                            <Button
                                label="Discard"
                                severity="secondary"
                                text
                                @click="form.reset()"
                            />
                            <Button
                                label="Save Changes"
                                :loading="form.processing"
                                @click="save"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.edit-page {
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.member-header {
    display: flex;
    align-items: flex-start;
    gap: 1.25rem;
}

.member-avatar {
    flex-shrink: 0;
    background: #106B4F;
    color: white;
}

.member-details {
    flex: 1;
}

.member-name-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.25rem;
}

.member-name {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.member-email {
    margin: 0 0 0.75rem;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.member-dates {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.date-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--color-slate-400, #94a3b8);
}

.date-item i {
    font-size: 0.75rem;
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

/* Floating Save Bar */
.floating-save {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    padding: 1rem;
    pointer-events: none;
}

@media (min-width: 1024px) {
    .floating-save {
        left: 260px;
    }

    .sidebar-collapsed .floating-save {
        left: 72px;
    }
}

.save-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    max-width: 800px;
    margin: 0 auto;
    padding: 0.75rem 1rem;
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    pointer-events: auto;
}

.save-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.save-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

/* Transition */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>
