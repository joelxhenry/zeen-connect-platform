<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import MadeInJamaica from '@/components/common/MadeInJamaica.vue';
import FlashMessages from '@/components/error/FlashMessages.vue';
import { home } from '@/routes';
defineProps<{
    title?: string;
}>();
</script>

<template>

    <Head :title="title" />
    <FlashMessages />

    <div class="auth-layout">
        <!-- Left Panel - Marketing -->
        <div class="auth-marketing">
            <div class="marketing-content">
                <AppLink :href="home.url()" class="logo">Zeen</AppLink>

                <div class="marketing-text">
                    <h1>Book services<br />with ease</h1>
                    <p class="tagline">CLICK. BOOK. ZEEN.</p>
                    <p class="subtitle">Connect with trusted service providers in Jamaica</p>
                </div>

                <div class="decorative-pattern">
                    <div class="pattern-grid">
                        <div class="pattern-block"></div>
                        <div class="pattern-block"></div>
                        <div class="pattern-block"></div>
                        <div class="pattern-block"></div>
                    </div>
                </div>
            </div>

            <MadeInJamaica />
        </div>

        <!-- Right Panel - Form -->
        <div class="auth-form-panel">
            <div class="auth-form-container">
                <AppLink :href="home.url()" class="mobile-logo">Zeen</AppLink>

                <div class="form-content">
                    <slot />
                </div>

                <p class="auth-footer">
                    <AppLink :href="home.url()">
                        <i class="pi pi-arrow-left"></i>
                        Back to Home
                    </AppLink>
                </p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.auth-layout {
    min-height: 100vh;
    display: flex;
}

/* Left Marketing Panel */
.auth-marketing {
    width: 55%;
    background-color: rgba(16, 107, 79, 0.06);
    padding: 2.5rem;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
}

.marketing-content {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.logo {
    font-size: 1.75rem;
    font-weight: 700;
    color: #106B4F;
    text-decoration: none;
}

.marketing-text {
    margin-top: auto;
    margin-bottom: auto;
    max-width: 480px;
}

.marketing-text h1 {
    font-size: 3.5rem;
    font-weight: 700;
    color: #0D1F1B;
    line-height: 1.1;
    margin: 0 0 1.5rem 0;
}

.tagline {
    font-size: 1.25rem;
    font-weight: 600;
    color: #106B4F;
    letter-spacing: 0.15em;
    margin: 0 0 1rem 0;
}

.subtitle {
    font-size: 1.125rem;
    color: #6b7280;
    margin: 0;
}

.decorative-pattern {
    position: absolute;
    right: -60px;
    top: 50%;
    transform: translateY(-50%);
    opacity: 0.15;
}

.pattern-grid {
    display: grid;
    grid-template-columns: repeat(2, 80px);
    gap: 8px;
}

.pattern-block {
    width: 80px;
    height: 80px;
    background-color: #106B4F;
    border-radius: 12px;
}

.pattern-block:nth-child(2) {
    border-radius: 12px 12px 40px 12px;
}

.pattern-block:nth-child(3) {
    border-radius: 12px 40px 12px 12px;
}

/* Right Form Panel */
.auth-form-panel {
    width: 45%;
    background-color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.5rem;
}

.auth-form-container {
    width: 100%;
    max-width: 400px;
}

.mobile-logo {
    display: none;
    font-size: 1.75rem;
    font-weight: 700;
    color: #106B4F;
    text-decoration: none;
    text-align: center;
    margin-bottom: 2rem;
}

.form-content {
    margin-bottom: 2rem;
}

.auth-footer {
    text-align: center;
    margin: 0;
}

.auth-footer a {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
    text-decoration: none;
    transition: color 0.2s;
}

.auth-footer a:hover {
    color: #106B4F;
}

/* Responsive */
@media (max-width: 1024px) {
    .auth-marketing {
        width: 45%;
    }

    .auth-form-panel {
        width: 55%;
    }

    .marketing-text h1 {
        font-size: 2.5rem;
    }
}

@media (max-width: 768px) {
    .auth-layout {
        flex-direction: column;
    }

    .auth-marketing {
        display: none;
    }

    .auth-form-panel {
        width: 100%;
        min-height: 100vh;
        padding: 2rem 1.5rem;
    }

    .mobile-logo {
        display: block;
    }
}
</style>
