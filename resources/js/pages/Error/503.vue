<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineProps<{
    status?: number;
    message?: string;
}>();
</script>

<template>
    <Head title="Maintenance" />

    <div class="maintenance-page">
        <div class="maintenance-background">
            <div class="blob blob-1"></div>
            <div class="blob blob-2"></div>
            <div class="blob blob-3"></div>
        </div>

        <div class="maintenance-content">
            <div class="logo">Zeen</div>

            <div class="maintenance-icon">
                <i class="pi pi-wrench"></i>
            </div>

            <h1>We'll Be Right Back</h1>
            <p class="maintenance-message">
                {{ message || 'We\'re performing scheduled maintenance to improve your experience. Please check back shortly.' }}
            </p>

            <div class="status-indicator">
                <span class="pulse"></span>
                <span class="status-text">Maintenance in progress</span>
            </div>

            <div class="maintenance-footer">
                <p>Questions? Email us at <a href="mailto:support@getzeen.com">support@getzeen.com</a></p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.maintenance-page {
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(165deg, #0D1F1B 0%, #0f2922 50%, #0D1F1B 100%);
    padding: 2rem;
    position: relative;
    overflow: hidden;
    font-family: 'Outfit', sans-serif;
}

.maintenance-background {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}

.blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
}

.blob-1 {
    width: 600px;
    height: 600px;
    background: #106B4F;
    opacity: 0.3;
    top: -200px;
    right: -150px;
}

.blob-2 {
    width: 400px;
    height: 400px;
    background: #1ABC9C;
    opacity: 0.2;
    bottom: -100px;
    left: -100px;
}

.blob-3 {
    width: 300px;
    height: 300px;
    background: #F59E0B;
    opacity: 0.1;
    top: 40%;
    left: 20%;
}

.maintenance-content {
    position: relative;
    text-align: center;
    color: white;
    max-width: 500px;
    z-index: 1;
}

.logo {
    font-size: 2.5rem;
    font-weight: 700;
    font-style: italic;
    color: #1ABC9C;
    margin-bottom: 3rem;
}

.maintenance-icon {
    width: 100px;
    height: 100px;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 2.5rem;
}

.maintenance-icon i {
    font-size: 3rem;
    color: #1ABC9C;
}

.maintenance-content h1 {
    font-size: 2.5rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
    color: white;
}

.maintenance-message {
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.7;
    margin: 0 0 2.5rem 0;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.875rem 1.5rem;
    background: rgba(255, 255, 255, 0.08);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 100px;
    margin-bottom: 3rem;
}

.pulse {
    width: 10px;
    height: 10px;
    background: #1ABC9C;
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
        transform: scale(1);
    }
    50% {
        opacity: 0.5;
        transform: scale(1.3);
    }
}

.status-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: rgba(255, 255, 255, 0.8);
}

.maintenance-footer {
    padding-top: 2rem;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.maintenance-footer p {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.5);
    margin: 0;
}

.maintenance-footer a {
    color: #1ABC9C;
    text-decoration: none;
    transition: color 0.2s;
}

.maintenance-footer a:hover {
    color: #16a085;
}

/* Responsive */
@media (max-width: 768px) {
    .maintenance-page {
        padding: 1.5rem;
    }

    .logo {
        font-size: 2rem;
        margin-bottom: 2rem;
    }

    .maintenance-icon {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        margin-bottom: 2rem;
    }

    .maintenance-icon i {
        font-size: 2.25rem;
    }

    .maintenance-content h1 {
        font-size: 1.75rem;
    }

    .maintenance-message {
        font-size: 1rem;
    }
}
</style>
