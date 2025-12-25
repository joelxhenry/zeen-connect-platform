<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Button from 'primevue/button';
import RegisterController from '@/actions/App/Domains/Auth/Controllers/RegisterController';
import { login, terms, privacy } from '@/routes';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    business_name: '',
    phone: '',
});

const submit = () => {
    form.post(RegisterController.storeProvider.url(), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};

const socialAuth = (provider: string) => {
    // Social auth will be implemented with Laravel Socialite
    window.location.href = `/auth/${provider}`;
};
</script>

<template>
    <AuthLayout title="Register as Provider">
        <div class="register-form">
            <div class="form-header">
                <h2>Create Provider Account</h2>
                <p>Start growing your business with Zeen</p>
            </div>

            <!-- Social Auth Buttons -->
            <div class="social-auth">
                <button type="button" class="social-btn google-btn" @click="socialAuth('google')">
                    <svg class="social-icon" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continue with Google
                </button>
                <button type="button" class="social-btn apple-btn" @click="socialAuth('apple')">
                    <svg class="social-icon" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
                    </svg>
                    Continue with Apple
                </button>
            </div>

            <div class="divider">
                <span>or register with email</span>
            </div>

            <form @submit.prevent="submit">
                <div class="form-row">
                    <div class="form-field">
                        <label for="name">Full Name</label>
                        <InputText
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Your full name"
                            :class="{ 'p-invalid': form.errors.name }"
                            autofocus
                        />
                        <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                    </div>
                </div>

                <div class="form-field">
                    <label for="email">Email address</label>
                    <InputText
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="you@example.com"
                        :class="{ 'p-invalid': form.errors.email }"
                    />
                    <small v-if="form.errors.email" class="p-error">{{ form.errors.email }}</small>
                </div>

                <div class="form-row two-col">
                    <div class="form-field">
                        <label for="password">Password</label>
                        <Password
                            id="password"
                            v-model="form.password"
                            placeholder="Create password"
                            :feedback="false"
                            toggleMask
                            :class="{ 'p-invalid': form.errors.password }"
                            inputClass="w-full"
                        />
                        <small v-if="form.errors.password" class="p-error">{{ form.errors.password }}</small>
                    </div>
                    <div class="form-field">
                        <label for="password_confirmation">Confirm Password</label>
                        <Password
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            placeholder="Confirm password"
                            :feedback="false"
                            toggleMask
                            :class="{ 'p-invalid': form.errors.password_confirmation }"
                            inputClass="w-full"
                        />
                        <small v-if="form.errors.password_confirmation" class="p-error">{{ form.errors.password_confirmation }}</small>
                    </div>
                </div>

                <div class="form-field">
                    <label for="business_name">Business Name</label>
                    <InputText
                        id="business_name"
                        v-model="form.business_name"
                        type="text"
                        placeholder="Your business name"
                        :class="{ 'p-invalid': form.errors.business_name }"
                    />
                    <small v-if="form.errors.business_name" class="p-error">{{ form.errors.business_name }}</small>
                </div>

                <div class="form-field">
                    <label for="phone">Phone Number <span class="optional">(optional)</span></label>
                    <InputText
                        id="phone"
                        v-model="form.phone"
                        type="tel"
                        placeholder="(876) 555-0123"
                        :class="{ 'p-invalid': form.errors.phone }"
                    />
                    <small v-if="form.errors.phone" class="p-error">{{ form.errors.phone }}</small>
                </div>

                <p class="terms-text">
                    By creating an account, you agree to our
                    <Link :href="terms.url()">Terms of Service</Link> and
                    <Link :href="privacy.url()">Privacy Policy</Link>.
                </p>

                <Button
                    type="submit"
                    label="Create Account"
                    :loading="form.processing"
                    class="submit-btn"
                />
            </form>

            <p class="login-link">
                Already have an account? <Link :href="login.url()">Sign in</Link>
            </p>
        </div>
    </AuthLayout>
</template>

<style scoped>
.register-form {
    text-align: left;
}

.form-header {
    text-align: center;
    margin-bottom: 1.5rem;
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

/* Social Auth Buttons */
.social-auth {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.social-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    width: 100%;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s ease;
}

.social-icon {
    width: 20px;
    height: 20px;
}

.google-btn {
    background: white;
    border: 1px solid #e5e5e5;
    color: #0D1F1B;
}

.google-btn:hover {
    background: #f9fafb;
    border-color: #d1d5db;
}

.apple-btn {
    background: #000000;
    border: 1px solid #000000;
    color: white;
}

.apple-btn:hover {
    background: #1a1a1a;
}

/* Divider */
.divider {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.divider::before,
.divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e5e5;
}

.divider span {
    padding: 0 1rem;
    font-size: 0.8125rem;
    color: #9ca3af;
}

/* Form Fields */
.form-field {
    margin-bottom: 1rem;
}

.form-row.two-col {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-field label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.375rem;
}

.form-field label .optional {
    font-weight: 400;
    color: #9ca3af;
}

.form-field :deep(.p-inputtext) {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.9375rem;
}

.form-field :deep(.p-password) {
    width: 100%;
}

.form-field :deep(.p-password-input) {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border-radius: 0.5rem;
    font-size: 0.9375rem;
}

.p-error {
    color: #dc2626;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: block;
}

.terms-text {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 1rem 0 1.25rem;
    line-height: 1.5;
}

.terms-text a {
    color: #106B4F;
    text-decoration: none;
}

.terms-text a:hover {
    text-decoration: underline;
}

.submit-btn {
    width: 100%;
    padding: 0.75rem 1.5rem;
    background-color: #106B4F;
    border: none;
    border-radius: 0.5rem;
    font-weight: 500;
    justify-content: center;
}

.submit-btn:hover {
    background-color: #0D5A42;
}

.login-link {
    text-align: center;
    margin-top: 1.25rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.login-link a {
    color: #106B4F;
    text-decoration: none;
    font-weight: 500;
}

.login-link a:hover {
    text-decoration: underline;
}

.client-link {
    text-align: center;
    margin-top: 0.5rem;
    font-size: 0.8125rem;
    color: #9ca3af;
}

.client-link a {
    color: #106B4F;
    text-decoration: none;
}

.client-link a:hover {
    text-decoration: underline;
}

/* Responsive */
@media (max-width: 480px) {
    .form-row.two-col {
        grid-template-columns: 1fr;
    }
}
</style>
