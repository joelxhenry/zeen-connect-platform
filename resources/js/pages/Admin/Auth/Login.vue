<script setup lang="ts">
import { useForm, Head } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/login', {
        onFinish: () => {
            form.reset('password');
        },
    });
};
</script>

<template>
    <Head title="Admin Login" />

    <div class="admin-login">
        <div class="login-container">
            <div class="login-card">
                <!-- Logo -->
                <div class="logo">
                    <svg width="48" height="48" viewBox="0 0 40 40" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect width="40" height="40" rx="8" fill="#106B4F"/>
                        <path d="M12 28L20 12L28 28H12Z" fill="white"/>
                    </svg>
                </div>

                <div class="login-header">
                    <h1>Admin Portal</h1>
                    <p>Sign in to access the admin dashboard</p>
                </div>

                <form @submit.prevent="submit" class="login-form">
                    <div class="form-field">
                        <label for="email">Email address</label>
                        <InputText
                            id="email"
                            v-model="form.email"
                            type="email"
                            placeholder="admin@example.com"
                            :class="{ 'p-invalid': form.errors.email }"
                            autofocus
                        />
                        <small v-if="form.errors.email" class="error-message">{{ form.errors.email }}</small>
                    </div>

                    <div class="form-field">
                        <label for="password">Password</label>
                        <Password
                            id="password"
                            v-model="form.password"
                            placeholder="Enter your password"
                            :feedback="false"
                            toggleMask
                            :class="{ 'p-invalid': form.errors.password }"
                            inputClass="w-full"
                        />
                        <small v-if="form.errors.password" class="error-message">{{ form.errors.password }}</small>
                    </div>

                    <div class="form-options">
                        <div class="remember-me">
                            <Checkbox
                                v-model="form.remember"
                                inputId="remember"
                                :binary="true"
                            />
                            <label for="remember">Remember me</label>
                        </div>
                    </div>

                    <Button
                        type="submit"
                        label="Sign In"
                        :loading="form.processing"
                        class="submit-btn"
                    />
                </form>

                <div class="login-footer">
                    <p>Protected area. Authorized personnel only.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.admin-login {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #0D1F1B 0%, #1a3a32 100%);
    padding: 1rem;
}

.login-container {
    width: 100%;
    max-width: 420px;
}

.login-card {
    background: #ffffff;
    border-radius: 1rem;
    padding: 2.5rem;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4);
}

.logo {
    display: flex;
    justify-content: center;
    margin-bottom: 1.5rem;
}

.login-header {
    text-align: center;
    margin-bottom: 2rem;
}

.login-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #0D1F1B;
    margin: 0 0 0.5rem 0;
}

.login-header p {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
}

.login-form {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-field label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.form-field :deep(.p-inputtext) {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    border: 1px solid #d1d5db;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-field :deep(.p-inputtext:focus) {
    border-color: #106B4F;
    box-shadow: 0 0 0 3px rgba(16, 107, 79, 0.1);
}

.form-field :deep(.p-password) {
    width: 100%;
}

.form-field :deep(.p-password-input) {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
}

.form-field :deep(.p-invalid) {
    border-color: #dc2626;
}

.error-message {
    color: #dc2626;
    font-size: 0.75rem;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.remember-me {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.remember-me label {
    font-size: 0.875rem;
    color: #374151;
    cursor: pointer;
}

.remember-me :deep(.p-checkbox .p-checkbox-box.p-highlight) {
    background: #106B4F;
    border-color: #106B4F;
}

.submit-btn {
    width: 100%;
    padding: 0.875rem 1.5rem;
    background: #106B4F;
    border: none;
    border-radius: 0.5rem;
    font-weight: 600;
    font-size: 0.9375rem;
    justify-content: center;
    margin-top: 0.5rem;
    transition: background 0.2s;
}

.submit-btn:hover:not(:disabled) {
    background: #0D5A42;
}

.submit-btn:focus {
    box-shadow: 0 0 0 3px rgba(16, 107, 79, 0.3);
}

.login-footer {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e7eb;
    text-align: center;
}

.login-footer p {
    font-size: 0.75rem;
    color: #9ca3af;
    margin: 0;
}
</style>
