<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/components/layout/DashboardLayout.vue';
import Card from 'primevue/card';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Checkbox from 'primevue/checkbox';
import FileUpload from 'primevue/fileupload';
import Dialog from 'primevue/dialog';
import Password from 'primevue/password';
import Message from 'primevue/message';

interface UserData {
    id: number;
    name: string;
    email: string;
    phone?: string;
    avatar?: string;
    preferred_location_id?: number;
    notification_preferences: {
        email_booking_updates: boolean;
        email_reminders: boolean;
        email_promotions: boolean;
    };
    created_at: string;
}

const props = defineProps<{
    user: UserData;
}>();

const profileForm = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone || '',
    notification_preferences: {
        email_booking_updates: props.user.notification_preferences?.email_booking_updates ?? true,
        email_reminders: props.user.notification_preferences?.email_reminders ?? true,
        email_promotions: props.user.notification_preferences?.email_promotions ?? false,
    },
});

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showPasswordDialog = ref(false);
const showDeleteDialog = ref(false);

const deleteForm = useForm({
    password: '',
});

const updateProfile = () => {
    profileForm.put(route('client.profile.update'), {
        preserveScroll: true,
    });
};

const updatePassword = () => {
    passwordForm.put(route('client.profile.password'), {
        preserveScroll: true,
        onSuccess: () => {
            showPasswordDialog.value = false;
            passwordForm.reset();
        },
    });
};

const deleteAccount = () => {
    deleteForm.delete(route('client.profile.destroy'), {
        onSuccess: () => {
            showDeleteDialog.value = false;
        },
    });
};

const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};
</script>

<template>
    <DashboardLayout title="Profile">
        <Head title="My Profile" />

        <div class="profile-page">
            <div class="page-header">
                <h1 class="page-title">My Profile</h1>
                <span class="member-since">Member since {{ user.created_at }}</span>
            </div>

            <div class="profile-grid">
                <!-- Profile Info Card -->
                <Card class="profile-card">
                    <template #title>
                        <div class="card-header">
                            <i class="pi pi-user"></i>
                            <span>Personal Information</span>
                        </div>
                    </template>
                    <template #content>
                        <form @submit.prevent="updateProfile" class="profile-form">
                            <div class="avatar-section">
                                <div class="avatar-wrapper">
                                    <img
                                        v-if="user.avatar"
                                        :src="user.avatar"
                                        :alt="user.name"
                                        class="avatar"
                                    />
                                    <div v-else class="avatar-placeholder">
                                        {{ getInitials(user.name) }}
                                    </div>
                                </div>
                                <div class="avatar-actions">
                                    <Button
                                        label="Change Photo"
                                        icon="pi pi-camera"
                                        size="small"
                                        outlined
                                        disabled
                                    />
                                    <small class="hint">Photo upload coming soon</small>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-field">
                                    <label for="name">Full Name</label>
                                    <InputText
                                        id="name"
                                        v-model="profileForm.name"
                                        :class="{ 'p-invalid': profileForm.errors.name }"
                                        class="w-full"
                                    />
                                    <small class="error" v-if="profileForm.errors.name">
                                        {{ profileForm.errors.name }}
                                    </small>
                                </div>

                                <div class="form-field">
                                    <label for="email">Email Address</label>
                                    <InputText
                                        id="email"
                                        v-model="profileForm.email"
                                        type="email"
                                        :class="{ 'p-invalid': profileForm.errors.email }"
                                        class="w-full"
                                    />
                                    <small class="error" v-if="profileForm.errors.email">
                                        {{ profileForm.errors.email }}
                                    </small>
                                </div>

                                <div class="form-field">
                                    <label for="phone">Phone Number</label>
                                    <InputText
                                        id="phone"
                                        v-model="profileForm.phone"
                                        placeholder="876-555-1234"
                                        :class="{ 'p-invalid': profileForm.errors.phone }"
                                        class="w-full"
                                    />
                                    <small class="error" v-if="profileForm.errors.phone">
                                        {{ profileForm.errors.phone }}
                                    </small>
                                </div>
                            </div>

                            <div class="form-actions">
                                <Button
                                    type="submit"
                                    label="Save Changes"
                                    icon="pi pi-check"
                                    :loading="profileForm.processing"
                                    :disabled="!profileForm.isDirty"
                                />
                            </div>
                        </form>
                    </template>
                </Card>

                <!-- Notification Preferences -->
                <Card class="notifications-card">
                    <template #title>
                        <div class="card-header">
                            <i class="pi pi-bell"></i>
                            <span>Notification Preferences</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="notifications-list">
                            <div class="notification-item">
                                <div class="notification-info">
                                    <span class="notification-label">Booking Updates</span>
                                    <span class="notification-desc">Receive emails about booking confirmations and status changes</span>
                                </div>
                                <Checkbox
                                    v-model="profileForm.notification_preferences.email_booking_updates"
                                    :binary="true"
                                />
                            </div>

                            <div class="notification-item">
                                <div class="notification-info">
                                    <span class="notification-label">Appointment Reminders</span>
                                    <span class="notification-desc">Get reminded about upcoming appointments</span>
                                </div>
                                <Checkbox
                                    v-model="profileForm.notification_preferences.email_reminders"
                                    :binary="true"
                                />
                            </div>

                            <div class="notification-item">
                                <div class="notification-info">
                                    <span class="notification-label">Promotions & Updates</span>
                                    <span class="notification-desc">Receive news about special offers and platform updates</span>
                                </div>
                                <Checkbox
                                    v-model="profileForm.notification_preferences.email_promotions"
                                    :binary="true"
                                />
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Security Card -->
                <Card class="security-card">
                    <template #title>
                        <div class="card-header">
                            <i class="pi pi-shield"></i>
                            <span>Security</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="security-actions">
                            <div class="security-item">
                                <div class="security-info">
                                    <span class="security-label">Password</span>
                                    <span class="security-desc">Change your account password</span>
                                </div>
                                <Button
                                    label="Change Password"
                                    icon="pi pi-lock"
                                    severity="secondary"
                                    outlined
                                    @click="showPasswordDialog = true"
                                />
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- Danger Zone -->
                <Card class="danger-card">
                    <template #title>
                        <div class="card-header danger">
                            <i class="pi pi-exclamation-triangle"></i>
                            <span>Danger Zone</span>
                        </div>
                    </template>
                    <template #content>
                        <div class="danger-content">
                            <p class="danger-text">
                                Once you delete your account, there is no going back. All your data will be permanently removed.
                            </p>
                            <Button
                                label="Delete Account"
                                icon="pi pi-trash"
                                severity="danger"
                                outlined
                                @click="showDeleteDialog = true"
                            />
                        </div>
                    </template>
                </Card>
            </div>
        </div>

        <!-- Password Dialog -->
        <Dialog
            v-model:visible="showPasswordDialog"
            modal
            header="Change Password"
            :style="{ width: '450px' }"
        >
            <form @submit.prevent="updatePassword" class="password-form">
                <div class="form-field">
                    <label for="current_password">Current Password</label>
                    <Password
                        id="current_password"
                        v-model="passwordForm.current_password"
                        toggleMask
                        :feedback="false"
                        :class="{ 'p-invalid': passwordForm.errors.current_password }"
                        class="w-full"
                    />
                    <small class="error" v-if="passwordForm.errors.current_password">
                        {{ passwordForm.errors.current_password }}
                    </small>
                </div>

                <div class="form-field">
                    <label for="password">New Password</label>
                    <Password
                        id="password"
                        v-model="passwordForm.password"
                        toggleMask
                        :class="{ 'p-invalid': passwordForm.errors.password }"
                        class="w-full"
                    />
                    <small class="error" v-if="passwordForm.errors.password">
                        {{ passwordForm.errors.password }}
                    </small>
                </div>

                <div class="form-field">
                    <label for="password_confirmation">Confirm New Password</label>
                    <Password
                        id="password_confirmation"
                        v-model="passwordForm.password_confirmation"
                        toggleMask
                        :feedback="false"
                        class="w-full"
                    />
                </div>
            </form>

            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    outlined
                    @click="showPasswordDialog = false"
                />
                <Button
                    label="Update Password"
                    icon="pi pi-check"
                    :loading="passwordForm.processing"
                    @click="updatePassword"
                />
            </template>
        </Dialog>

        <!-- Delete Account Dialog -->
        <Dialog
            v-model:visible="showDeleteDialog"
            modal
            header="Delete Account"
            :style="{ width: '450px' }"
        >
            <Message severity="error" :closable="false">
                This action cannot be undone. All your data will be permanently deleted.
            </Message>

            <div class="delete-form">
                <p>Please enter your password to confirm account deletion:</p>
                <div class="form-field">
                    <Password
                        v-model="deleteForm.password"
                        toggleMask
                        :feedback="false"
                        placeholder="Your password"
                        :class="{ 'p-invalid': deleteForm.errors.password }"
                        class="w-full"
                    />
                    <small class="error" v-if="deleteForm.errors.password">
                        {{ deleteForm.errors.password }}
                    </small>
                </div>
            </div>

            <template #footer>
                <Button
                    label="Cancel"
                    severity="secondary"
                    outlined
                    @click="showDeleteDialog = false"
                />
                <Button
                    label="Delete My Account"
                    icon="pi pi-trash"
                    severity="danger"
                    :loading="deleteForm.processing"
                    :disabled="!deleteForm.password"
                    @click="deleteAccount"
                />
            </template>
        </Dialog>
    </DashboardLayout>
</template>

<style scoped>
.profile-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--p-surface-900);
    margin: 0;
}

.member-since {
    font-size: 0.875rem;
    color: var(--p-surface-500);
}

.profile-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.profile-card,
.notifications-card,
.security-card,
.danger-card {
    border-radius: 12px;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
}

.card-header i {
    color: var(--p-primary-color);
}

.card-header.danger i {
    color: var(--p-red-500);
}

.profile-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.avatar-section {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 1px solid var(--p-surface-200);
}

.avatar-wrapper {
    position: relative;
}

.avatar {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    object-fit: cover;
}

.avatar-placeholder {
    width: 80px;
    height: 80px;
    border-radius: 16px;
    background: linear-gradient(135deg, var(--p-primary-color), var(--p-primary-400));
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.5rem;
}

.avatar-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.hint {
    color: var(--p-surface-400);
    font-size: 0.75rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-field label {
    font-weight: 500;
    color: var(--p-surface-700);
    font-size: 0.875rem;
}

.error {
    color: var(--p-red-500);
    font-size: 0.75rem;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 1rem;
    border-top: 1px solid var(--p-surface-200);
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--p-surface-50);
    border-radius: 8px;
}

.notification-info {
    display: flex;
    flex-direction: column;
}

.notification-label {
    font-weight: 500;
    color: var(--p-surface-900);
}

.notification-desc {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.security-actions {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.security-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--p-surface-50);
    border-radius: 8px;
}

.security-info {
    display: flex;
    flex-direction: column;
}

.security-label {
    font-weight: 500;
    color: var(--p-surface-900);
}

.security-desc {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.danger-card {
    background: var(--p-red-50);
    border: 1px solid var(--p-red-200);
}

.danger-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.danger-text {
    color: var(--p-red-700);
    font-size: 0.875rem;
    margin: 0;
}

.password-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.delete-form {
    margin-top: 1rem;
}

.delete-form p {
    color: var(--p-surface-600);
    margin: 0 0 1rem 0;
}

@media (max-width: 768px) {
    .form-grid {
        grid-template-columns: 1fr;
    }

    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }

    .avatar-section {
        flex-direction: column;
        text-align: center;
    }

    .notification-item,
    .security-item {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>
