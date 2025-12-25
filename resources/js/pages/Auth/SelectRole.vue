<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import Button from 'primevue/button';

interface Props {
    roles: string[];
}

const props = defineProps<Props>();

const form = useForm({
    role: '',
});

const roleInfo: Record<string, { label: string; description: string; icon: string }> = {
    client: {
        label: 'Client',
        description: 'Book services and manage your appointments',
        icon: 'pi pi-user',
    },
    provider: {
        label: 'Service Provider',
        description: 'Manage your business and bookings',
        icon: 'pi pi-briefcase',
    },
    admin: {
        label: 'Administrator',
        description: 'Access the admin dashboard',
        icon: 'pi pi-shield',
    },
};

const selectRole = (role: string) => {
    form.role = role;
    form.post('/login/select-role');
};
</script>

<template>
    <AuthLayout title="Select Account">
        <div class="select-role">
            <div class="form-header">
                <h2>Choose Account</h2>
                <p>Multiple accounts found. Select which one to sign in with.</p>
            </div>

            <div class="roles-list">
                <button
                    v-for="role in props.roles"
                    :key="role"
                    type="button"
                    class="role-card"
                    :class="{ selected: form.role === role }"
                    :disabled="form.processing"
                    @click="selectRole(role)"
                >
                    <div class="role-icon">
                        <i :class="roleInfo[role]?.icon || 'pi pi-user'"></i>
                    </div>
                    <div class="role-info">
                        <h3>{{ roleInfo[role]?.label || role }}</h3>
                        <p>{{ roleInfo[role]?.description || '' }}</p>
                    </div>
                    <div class="role-arrow">
                        <i class="pi pi-chevron-right"></i>
                    </div>
                </button>
            </div>

            <div v-if="form.errors.role" class="error-message">
                {{ form.errors.role }}
            </div>

            <div class="back-link">
                <AppLink href="/login">
                    <i class="pi pi-arrow-left"></i>
                    Back to login
                </AppLink>
            </div>
        </div>
    </AuthLayout>
</template>

<style scoped>
.select-role {
    text-align: left;
}

.form-header {
    text-align: center;
    margin-bottom: 2rem;
}

.form-header h2 {
    font-size: 1.5rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0 0 0.5rem 0;
}

.form-header p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.roles-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.role-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 1rem;
    background: #ffffff;
    border: 2px solid #e5e7eb;
    border-radius: 0.75rem;
    cursor: pointer;
    text-align: left;
    transition: all 0.2s ease;
}

.role-card:hover:not(:disabled) {
    border-color: #106B4F;
    background: #f9fafb;
}

.role-card.selected {
    border-color: #106B4F;
    background: #f0fdf4;
}

.role-card:disabled {
    opacity: 0.7;
    cursor: wait;
}

.role-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    background: #f3f4f6;
    border-radius: 0.5rem;
    flex-shrink: 0;
}

.role-icon i {
    font-size: 1.25rem;
    color: #106B4F;
}

.role-info {
    flex: 1;
}

.role-info h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0 0 0.25rem 0;
}

.role-info p {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0;
}

.role-arrow {
    flex-shrink: 0;
}

.role-arrow i {
    color: #9ca3af;
}

.error-message {
    color: #dc2626;
    font-size: 0.875rem;
    text-align: center;
    margin-bottom: 1rem;
}

.back-link {
    text-align: center;
}

.back-link a {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
    text-decoration: none;
}

.back-link a:hover {
    color: #106B4F;
}
</style>
