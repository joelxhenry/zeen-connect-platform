<script setup lang="ts">
import { useForm, Link } from '@inertiajs/vue3';
import AuthLayout from '@/components/layout/AuthLayout.vue';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import Divider from 'primevue/divider';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    business_name: '',
    bio: '',
    tagline: '',
    location: '',
});

const submit = () => {
    form.post('/register/provider', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthLayout title="Become a Provider">
        <div class="text-center mb-6">
            <h2 class="page-title">Become a Provider</h2>
            <p class="page-subtitle">Start offering your services on Zeen</p>
        </div>

        <form @submit.prevent="submit" class="auth-form">
            <!-- Personal Information -->
            <Divider align="left">
                <span class="divider-label">Personal Information</span>
            </Divider>

            <div class="form-field">
                <label for="name" class="form-label">Full name</label>
                <div class="input-wrapper">
                    <i class="pi pi-user input-icon"></i>
                    <InputText
                        id="name"
                        v-model="form.name"
                        autocomplete="name"
                        placeholder="Enter your full name"
                        class="w-full pl-10"
                        :class="{ 'p-invalid': form.errors.name }"
                    />
                </div>
                <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-field">
                    <label for="email" class="form-label">Email</label>
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
                    <label for="phone" class="form-label">Phone</label>
                    <div class="input-wrapper">
                        <i class="pi pi-phone input-icon"></i>
                        <InputText
                            id="phone"
                            v-model="form.phone"
                            type="tel"
                            autocomplete="tel"
                            placeholder="+1876 123 4567"
                            class="w-full pl-10"
                        />
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-field">
                    <label for="password" class="form-label">Password</label>
                    <Password
                        id="password"
                        v-model="form.password"
                        autocomplete="new-password"
                        placeholder="Create a password"
                        toggleMask
                        class="w-full"
                        inputClass="w-full"
                        :class="{ 'p-invalid': form.errors.password }"
                    />
                    <small v-if="form.errors.password" class="p-error">{{ form.errors.password }}</small>
                </div>

                <div class="form-field">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <Password
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                        toggleMask
                        :feedback="false"
                        class="w-full"
                        inputClass="w-full"
                    />
                </div>
            </div>

            <!-- Business Information -->
            <Divider align="left">
                <span class="divider-label">Business Information</span>
            </Divider>

            <div class="form-field">
                <label for="business_name" class="form-label">Business name</label>
                <div class="input-wrapper">
                    <i class="pi pi-building input-icon"></i>
                    <InputText
                        id="business_name"
                        v-model="form.business_name"
                        placeholder="e.g., John's Barbershop"
                        class="w-full pl-10"
                        :class="{ 'p-invalid': form.errors.business_name }"
                    />
                </div>
                <small v-if="form.errors.business_name" class="p-error">{{ form.errors.business_name }}</small>
            </div>

            <div class="form-field">
                <label for="tagline" class="form-label">Tagline (optional)</label>
                <InputText
                    id="tagline"
                    v-model="form.tagline"
                    placeholder="e.g., Quality cuts, every time"
                    class="w-full"
                />
            </div>

            <div class="form-field">
                <label for="location" class="form-label">Location (optional)</label>
                <div class="input-wrapper">
                    <i class="pi pi-map-marker input-icon"></i>
                    <InputText
                        id="location"
                        v-model="form.location"
                        placeholder="e.g., Kingston, Jamaica"
                        class="w-full pl-10"
                    />
                </div>
            </div>

            <div class="form-field">
                <label for="bio" class="form-label">About your business (optional)</label>
                <Textarea
                    id="bio"
                    v-model="form.bio"
                    rows="3"
                    placeholder="Tell clients about your experience and services..."
                    class="w-full"
                />
            </div>

            <Button
                type="submit"
                :label="form.processing ? 'Creating your profile...' : 'Create Provider Account'"
                :loading="form.processing"
                class="w-full"
            />
        </form>

        <div class="mt-6 text-center">
            <p class="switch-text">
                Already have an account?
                <Link href="/login" class="switch-link">Sign in</Link>
            </p>
            <p class="switch-text mt-2">
                Just looking to book services?
                <Link href="/register" class="switch-link">Register as a Client</Link>
            </p>
        </div>
    </AuthLayout>
</template>

<style scoped>
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

.divider-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
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
