<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
</script>

<template>
    <PublicLayout>
        <Head title="Welcome" />

        <div class="welcome-page">
            <div class="hero">
                <h1>Zeen Connect</h1>
                <p class="tagline">CLICK. BOOK. ZEEN.</p>
                <p class="subtitle">Connect with service providers in Jamaica</p>

                <div class="actions">
                    <Link href="/explore" class="btn btn-primary">Explore Providers</Link>
                    <Link href="/register" class="btn btn-secondary">Get Started</Link>
                </div>
            </div>
        </div>
    </PublicLayout>
</template>

<style scoped>
.welcome-page {
    min-height: calc(100vh - 140px);
    display: flex;
    align-items: center;
    justify-content: center;
}

.hero {
    text-align: center;
    padding: 2rem;
}

.hero h1 {
    font-size: 3rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 0.5rem;
}

.tagline {
    font-size: 1.5rem;
    font-weight: 600;
    color: #106B4F;
    margin-bottom: 0.5rem;
    letter-spacing: 0.1em;
}

.subtitle {
    font-size: 1.25rem;
    color: #6b7280;
    margin-bottom: 2rem;
}

.actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-weight: 500;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-primary {
    background-color: #106B4F;
    color: white;
}

.btn-primary:hover {
    background-color: #0D5A42;
}

.btn-secondary {
    background-color: #F5F5F5;
    color: #0D1F1B;
}

.btn-secondary:hover {
    background-color: #e5e5e5;
}
</style>
