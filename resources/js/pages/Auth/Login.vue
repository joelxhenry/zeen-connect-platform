<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Card from 'primevue/card';
import Button from 'primevue/button';
import Divider from 'primevue/divider';
</script>

<template>
    <Head title="Sign In" />
    <div class="login-selector">
        <div class="logo-container">
            <Link href="/" class="logo-link">
                <span class="logo">Zeen</span>
            </Link>
        </div>

        <div class="card-container">
            <Card class="selector-card">
                <template #content>
                    <div class="text-center mb-6">
                        <h2 class="page-title">Welcome Back</h2>
                        <p class="page-subtitle">Choose how you'd like to sign in</p>
                    </div>

                    <div class="options-grid">
                        <!-- Client Login Option -->
                        <Link href="/login/client" class="option-link">
                            <Card class="option-card">
                                <template #content>
                                    <div class="option-content">
                                        <i class="pi pi-user option-icon"></i>
                                        <h3 class="option-title">I'm a Client</h3>
                                        <p class="option-text">
                                            Book services and manage your appointments
                                        </p>
                                    </div>
                                </template>
                            </Card>
                        </Link>

                        <!-- Provider Login Option -->
                        <Link href="/login/provider" class="option-link">
                            <Card class="option-card">
                                <template #content>
                                    <div class="option-content">
                                        <i class="pi pi-shop option-icon"></i>
                                        <h3 class="option-title">I'm a Provider</h3>
                                        <p class="option-text">
                                            Manage your business and bookings
                                        </p>
                                    </div>
                                </template>
                            </Card>
                        </Link>
                    </div>

                    <Divider align="center">
                        <span class="divider-text">New to Zeen?</span>
                    </Divider>

                    <div class="signup-buttons">
                        <Link href="/register" class="signup-link">
                            <Button label="Sign up as Client" severity="secondary" outlined class="w-full" />
                        </Link>
                        <Link href="/register/provider" class="signup-link">
                            <Button label="Become a Provider" severity="secondary" outlined class="w-full" />
                        </Link>
                    </div>
                </template>
            </Card>
        </div>
    </div>
</template>

<style scoped>
.login-selector {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    padding: 3rem 1rem;
    background-color: var(--color-surface-alt);
}

.logo-container {
    text-align: center;
    margin-bottom: 2rem;
}

.logo-link {
    display: inline-block;
}

.logo {
    font-size: 2rem;
    font-weight: bold;
    color: var(--color-primary);
}

.card-container {
    margin: 0 auto;
    width: 100%;
    max-width: 32rem;
}

.selector-card {
    background-color: var(--color-surface);
    border-radius: 0.75rem;
}

.selector-card :deep(.p-card-body) {
    padding: 2rem;
}

.selector-card :deep(.p-card-content) {
    padding: 0;
}

.page-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--color-text-primary);
    margin: 0;
}

.page-subtitle {
    margin-top: 0.5rem;
    color: var(--color-text-secondary);
    font-size: 0.875rem;
}

.options-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
    margin-bottom: 1.5rem;
}

@media (max-width: 640px) {
    .options-grid {
        grid-template-columns: 1fr;
    }
}

.option-link {
    text-decoration: none;
    display: block;
}

.option-card {
    height: 100%;
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid transparent;
}

.option-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-2px);
}

.option-card :deep(.p-card-body) {
    padding: 1.5rem;
}

.option-card :deep(.p-card-content) {
    padding: 0;
}

.option-content {
    text-align: center;
}

.option-icon {
    font-size: 2.5rem;
    color: var(--color-primary);
    margin-bottom: 1rem;
}

.option-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-text-primary);
    margin: 0 0 0.5rem 0;
}

.option-text {
    font-size: 0.875rem;
    color: var(--color-text-secondary);
    margin: 0;
}

.divider-text {
    color: var(--color-text-muted);
    font-size: 0.875rem;
}

.signup-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

@media (max-width: 640px) {
    .signup-buttons {
        grid-template-columns: 1fr;
    }
}

.signup-link {
    text-decoration: none;
}
</style>
