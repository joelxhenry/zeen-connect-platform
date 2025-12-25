<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { home } from '@/routes';

interface Props {
    status: number;
    title: string;
    message: string;
    icon?: string;
    showHomeButton?: boolean;
    showBackButton?: boolean;
    showNavigation?: boolean;
}

withDefaults(defineProps<Props>(), {
    icon: 'pi pi-exclamation-circle',
    showHomeButton: true,
    showBackButton: true,
    showNavigation: true,
});

const goBack = () => {
    window.history.back();
};
</script>

<template>
    <Head :title="title" />

    <div class="error-page" :class="{ 'no-nav': !showNavigation }">
        <!-- Header -->
        <header v-if="showNavigation" class="error-header">
            <div class="header-container">
                <AppLink :href="home.url()" class="logo">Zeen</AppLink>
            </div>
        </header>

        <!-- Error Content -->
        <main class="error-main">
            <div class="error-background">
                <div class="error-blob blob-1"></div>
                <div class="error-blob blob-2"></div>
            </div>

            <div class="error-content">
                <div class="error-illustration">
                    <span class="error-code">{{ status }}</span>
                    <div class="error-icon-wrapper">
                        <i :class="icon" class="error-icon"></i>
                    </div>
                </div>

                <h1>{{ title }}</h1>
                <p class="error-message">{{ message }}</p>

                <div class="error-actions">
                    <button v-if="showBackButton" @click="goBack" class="btn btn-secondary">
                        <i class="pi pi-arrow-left"></i>
                        Go Back
                    </button>
                    <AppLink v-if="showHomeButton" :href="home.url()" class="btn btn-primary">
                        <i class="pi pi-home"></i>
                        Return Home
                    </AppLink>
                </div>

                <slot name="additional" />
            </div>
        </main>

        <!-- Footer -->
        <footer v-if="showNavigation" class="error-footer">
            <p>&copy; {{ new Date().getFullYear() }} Zeen. All rights reserved.</p>
        </footer>
    </div>
</template>

<style scoped>
.error-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: #fafbfc;
    font-family: 'Outfit', sans-serif;
}

.error-page.no-nav {
    justify-content: center;
}

/* Header */
.error-header {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: white;
    border-bottom: 1px solid #e5e7eb;
}

.header-container {
    max-width: 1200px;
    margin: 0 auto;
    height: 64px;
    display: flex;
    align-items: center;
    padding: 0 2rem;
}

.logo {
    font-size: 1.5rem;
    font-weight: 700;
    font-style: italic;
    color: #106B4F;
    text-decoration: none;
}

/* Main Content */
.error-main {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2rem;
    position: relative;
    overflow: hidden;
}

.error-page:not(.no-nav) .error-main {
    padding-top: calc(64px + 2rem);
}

.error-background {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.error-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    opacity: 0.15;
}

.blob-1 {
    width: 500px;
    height: 500px;
    background: #106B4F;
    top: -150px;
    right: -100px;
}

.blob-2 {
    width: 400px;
    height: 400px;
    background: #1ABC9C;
    bottom: -100px;
    left: -100px;
}

.error-content {
    position: relative;
    text-align: center;
    max-width: 480px;
    z-index: 1;
}

.error-illustration {
    position: relative;
    margin-bottom: 2rem;
    display: inline-block;
}

.error-code {
    font-size: 10rem;
    font-weight: 800;
    color: rgba(16, 107, 79, 0.08);
    line-height: 1;
    letter-spacing: -0.05em;
}

.error-icon-wrapper {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, #106B4F 0%, #1ABC9C 100%);
    border-radius: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 10px 40px rgba(16, 107, 79, 0.25);
}

.error-icon {
    font-size: 2.5rem;
    color: white;
}

.error-content h1 {
    font-size: 2rem;
    font-weight: 700;
    color: #0D1F1B;
    margin: 0 0 0.75rem 0;
}

.error-message {
    font-size: 1.125rem;
    color: #6b7280;
    line-height: 1.7;
    margin: 0 0 2rem 0;
}

.error-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
}

.btn-primary {
    background: linear-gradient(135deg, #106B4F 0%, #0D5A42 100%);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 107, 79, 0.25);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 107, 79, 0.35);
}

.btn-secondary {
    background: white;
    color: #0D1F1B;
    border: 2px solid #e5e7eb;
}

.btn-secondary:hover {
    border-color: #0D1F1B;
    background: #fafbfc;
}

/* Footer */
.error-footer {
    padding: 1.5rem 2rem;
    text-align: center;
    border-top: 1px solid #e5e7eb;
    background: white;
}

.error-footer p {
    margin: 0;
    font-size: 0.875rem;
    color: #9ca3af;
}

/* Responsive */
@media (max-width: 768px) {
    .header-container {
        padding: 0 1rem;
        height: 56px;
    }

    .error-page:not(.no-nav) .error-main {
        padding-top: calc(56px + 2rem);
    }

    .error-main {
        padding: 1.5rem;
    }

    .error-code {
        font-size: 6rem;
    }

    .error-icon-wrapper {
        width: 70px;
        height: 70px;
        border-radius: 18px;
    }

    .error-icon {
        font-size: 1.75rem;
    }

    .error-content h1 {
        font-size: 1.5rem;
    }

    .error-message {
        font-size: 1rem;
    }

    .error-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
