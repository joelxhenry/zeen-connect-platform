<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/components/layout/AuthLayout.vue';
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
    <AuthLayout title="Login">
        <div class="login-form">
            <div class="form-header">
                <h2>Welcome to Zeen</h2>
                <p>Let's sign you in</p>
            </div>

            <form @submit.prevent="submit">
                <div class="form-field">
                    <label for="email">Email address</label>
                    <InputText
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="Enter your email"
                        :class="{ 'p-invalid': form.errors.email }"
                        autofocus
                    />
                    <small v-if="form.errors.email" class="p-error">{{ form.errors.email }}</small>
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
                    <small v-if="form.errors.password" class="p-error">{{ form.errors.password }}</small>
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
                    <Link href="/forgot-password" class="forgot-link">Forgot password?</Link>
                </div>

                <Button
                    type="submit"
                    label="Login"
                    :loading="form.processing"
                    class="submit-btn"
                />
            </form>

            <p class="register-link">
                Don't have an account? <Link href="/register">Sign up</Link>
            </p>
        </div>
    </AuthLayout>
</template>

<style scoped>
.login-form {
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

.form-field {
    margin-bottom: 1.25rem;
}

.form-field label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.form-field :deep(.p-inputtext) {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
}

.form-field :deep(.p-password) {
    width: 100%;
}

.form-field :deep(.p-password-input) {
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
}

.p-error {
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: block;
}

.form-options {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
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

.forgot-link {
    font-size: 0.875rem;
    color: #106B4F;
    text-decoration: none;
}

.forgot-link:hover {
    text-decoration: underline;
}

.submit-btn {
    width: 100%;
    padding: 0.875rem 1.5rem;
    background-color: #106B4F;
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    justify-content: center;
}

.submit-btn:hover {
    background-color: #0D5A42;
}

.register-link {
    text-align: center;
    margin-top: 1.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.register-link a {
    color: #106B4F;
    text-decoration: none;
    font-weight: 500;
}

.register-link a:hover {
    text-decoration: underline;
}
</style>
