<script setup lang="ts">
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import FlashMessages from '@/components/error/FlashMessages.vue';
import { home } from '@/routes';
import { resolveUrl } from '@/utils/url';

defineProps<{
    title?: string;
    step?: 'checkout' | 'processing' | 'success' | 'failed';
}>();

const homeUrl = computed(() => resolveUrl(home().url));
</script>

<template>
    <Head :title="title ? `${title} | Zeen` : 'Payment | Zeen'" />
    <FlashMessages />

    <div class="payment-layout">
        <!-- Header -->
        <header class="payment-header">
            <div class="header-content">
                <AppLink :href="homeUrl" class="logo">
                    <span class="logo-text">Zeen</span>
                </AppLink>

                <div class="security-badge">
                    <i class="pi pi-shield-alt"></i>
                    <span>Secure Payment</span>
                </div>
            </div>
        </header>

        <!-- Progress Indicator -->
        <div v-if="step && step !== 'success' && step !== 'failed'" class="progress-bar">
            <div class="progress-track">
                <div
                    class="progress-fill"
                    :style="{ width: step === 'checkout' ? '50%' : step === 'processing' ? '75%' : '100%' }"
                ></div>
            </div>
        </div>

        <!-- Main Content -->
        <main class="payment-main">
            <div class="payment-container">
                <slot />
            </div>
        </main>

        <!-- Footer -->
        <footer class="payment-footer">
            <div class="footer-content">
                <div class="trust-indicators">
                    <div class="trust-item">
                        <i class="pi pi-lock"></i>
                        <span>256-bit SSL</span>
                    </div>
                    <div class="trust-item">
                        <i class="pi pi-shield"></i>
                        <span>PCI Compliant</span>
                    </div>
                    <div class="trust-item">
                        <i class="pi pi-verified"></i>
                        <span>Secured by WiPay</span>
                    </div>
                </div>
                <p class="copyright">&copy; {{ new Date().getFullYear() }} Zeen Connect. All rights reserved.</p>
            </div>
        </footer>

        <!-- Background Decoration -->
        <div class="bg-decoration">
            <div class="bg-gradient"></div>
            <div class="bg-pattern"></div>
        </div>
    </div>
</template>

<style scoped>
.payment-layout {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    position: relative;
    background-color: #fafbfc;
}

/* Header */
.payment-header {
    position: relative;
    z-index: 10;
    background: rgba(255, 255, 255, 0.9);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(229, 231, 235, 0.8);
}

.header-content {
    max-width: 1200px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo {
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.logo-text {
    font-size: 1.5rem;
    font-weight: 700;
    color: #106B4F;
}

.security-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, rgba(16, 107, 79, 0.08), rgba(16, 107, 79, 0.04));
    border: 1px solid rgba(16, 107, 79, 0.15);
    border-radius: 2rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #106B4F;
}

.security-badge i {
    font-size: 0.875rem;
}

/* Progress Bar */
.progress-bar {
    position: relative;
    z-index: 10;
    padding: 0 1.5rem;
    background: white;
}

.progress-track {
    max-width: 400px;
    margin: 0 auto;
    height: 3px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #106B4F, #14967a);
    border-radius: 2px;
    transition: width 0.4s ease;
}

/* Main Content */
.payment-main {
    flex: 1;
    position: relative;
    z-index: 5;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 2rem 1rem 4rem;
}

.payment-container {
    width: 100%;
    max-width: 480px;
}

/* Footer */
.payment-footer {
    position: relative;
    z-index: 10;
    background: white;
    border-top: 1px solid #e5e7eb;
    padding: 1.5rem;
}

.footer-content {
    max-width: 1200px;
    margin: 0 auto;
    text-align: center;
}

.trust-indicators {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.trust-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #6b7280;
}

.trust-item i {
    font-size: 0.875rem;
    color: #106B4F;
}

.copyright {
    font-size: 0.75rem;
    color: #9ca3af;
    margin: 0;
}

/* Background Decoration */
.bg-decoration {
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 0;
    overflow: hidden;
}

.bg-gradient {
    position: absolute;
    top: -50%;
    right: -20%;
    width: 80%;
    height: 100%;
    background: radial-gradient(ellipse at center, rgba(16, 107, 79, 0.03) 0%, transparent 70%);
    transform: rotate(-15deg);
}

.bg-pattern {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 300px;
    background: linear-gradient(to top, rgba(249, 250, 251, 1) 0%, transparent 100%);
}

/* Responsive */
@media (max-width: 640px) {
    .header-content {
        padding: 0.875rem 1rem;
    }

    .logo-text {
        font-size: 1.25rem;
    }

    .security-badge {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }

    .security-badge span {
        display: none;
    }

    .payment-main {
        padding: 1.5rem 1rem 3rem;
    }

    .trust-indicators {
        gap: 1rem;
    }

    .trust-item span {
        display: none;
    }
}
</style>
