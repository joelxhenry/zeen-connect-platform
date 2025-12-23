<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import Divider from 'primevue/divider';

const form = useForm({
    email: '',
    password: '',
    remember: false,
    login_type: 'provider',
});

const submit = () => {
    form.post('/login/provider', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthLayout title="Provider Login">
        <div class="text-center mb-6">
            <i class="pi pi-shop icon-primary"></i>
            <h2 class="page-title">Provider Sign In</h2>
            <p class="page-subtitle">Access your business console</p>
        </div>

        <form @submit.prevent="submit" class="auth-form">
            <div class="form-field">
                <label for="email" class="form-label">Email address</label>
                <div class="input-wrapper">
                    <i class="pi pi-envelope input-icon"></i>
                    <InputText
                        id="email"
                        v-model="form.email"
                        type="email"
                        autocomplete="email"
                        placeholder="Enter your email"
                        class="w-full pl-10"
                        :class="{ 'p-invalid': form.errors.email }"
                    />
                </div>
                <small v-if="form.errors.email" class="p-error">{{ form.errors.email }}</small>
            </div>

            <div class="form-field">
                <label for="password" class="form-label">Password</label>
                <Password
                    id="password"
                    v-model="form.password"
                    autocomplete="current-password"
                    placeholder="Enter your password"
                    toggleMask
                    :feedback="false"
                    class="w-full"
                    inputClass="w-full"
                    :class="{ 'p-invalid': form.errors.password }"
                />
                <small v-if="form.errors.password" class="p-error">{{ form.errors.password }}</small>
            </div>

            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <Checkbox v-model="form.remember" inputId="remember" :binary="true" />
                    <label for="remember" class="ml-2 text-sm">Remember me</label>
                </div>
                <Link href="/forgot-password" class="forgot-link">
                    Forgot your password?
                </Link>
            </div>

            <Button
                type="submit"
                :label="form.processing ? 'Signing in...' : 'Sign in to Console'"
                :loading="form.processing"
                class="w-full"
            />
        </form>

        <Divider align="center">
            <span class="divider-text">New provider?</span>
        </Divider>

        <Link href="/register/provider" class="block">
            <Button label="Create Provider Account" severity="secondary" outlined class="w-full" />
        </Link>

        <div class="mt-6 text-center">
            <p class="switch-text">
                Looking to book a service?
                <Link href="/login/client" class="switch-link">
                    Client Sign In
                </Link>
            </p>
        </div>
    </AuthLayout>
</template>

<style scoped>
.icon-primary {
    font-size: 3rem;
    color: var(--color-primary);
    display: block;
    margin-bottom: 0.75rem;
}

.page-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--color-text-primary);
    margin: 0;
}

.page-subtitle {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.auth-form {
    margin-top: 1.5rem;
}

.form-field {
    margin-bottom: 1.25rem;
}

.form-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-text-primary);
    margin-bottom: 0.5rem;
}

.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--color-text-muted);
    z-index: 1;
}

.forgot-link {
    font-size: 0.875rem;
    color: var(--color-primary);
    text-decoration: none;
}

.forgot-link:hover {
    color: var(--color-primary-dark);
}

.divider-text {
    color: var(--color-text-muted);
    font-size: 0.875rem;
}

.switch-text {
    color: var(--color-text-secondary);
    font-size: 0.875rem;
}

.switch-link {
    color: var(--color-primary);
    text-decoration: none;
    font-weight: 500;
}

.switch-link:hover {
    color: var(--color-primary-dark);
}
</style>
