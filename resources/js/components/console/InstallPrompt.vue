<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';
import Button from 'primevue/button';

interface BeforeInstallPromptEvent extends Event {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
}

const showPrompt = ref(false);
const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);

const STORAGE_KEY = 'zeen-pwa-prompt-dismissed';
const VISIT_COUNT_KEY = 'zeen-pwa-visit-count';

const isDismissed = () => {
    return localStorage.getItem(STORAGE_KEY) === 'true';
};

const getVisitCount = () => {
    return parseInt(localStorage.getItem(VISIT_COUNT_KEY) || '0', 10);
};

const incrementVisitCount = () => {
    const count = getVisitCount() + 1;
    localStorage.setItem(VISIT_COUNT_KEY, count.toString());
    return count;
};

const handleBeforeInstallPrompt = (e: Event) => {
    e.preventDefault();
    deferredPrompt.value = e as BeforeInstallPromptEvent;

    // Only show after 2nd visit and if not dismissed
    if (!isDismissed() && getVisitCount() >= 2) {
        showPrompt.value = true;
    }
};

const handleAppInstalled = () => {
    showPrompt.value = false;
    deferredPrompt.value = null;
};

const install = async () => {
    if (!deferredPrompt.value) return;

    await deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;

    if (outcome === 'accepted') {
        showPrompt.value = false;
    }

    deferredPrompt.value = null;
};

const dismiss = () => {
    showPrompt.value = false;
    localStorage.setItem(STORAGE_KEY, 'true');
};

onMounted(() => {
    incrementVisitCount();

    window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    window.addEventListener('appinstalled', handleAppInstalled);
});

onUnmounted(() => {
    window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
    window.removeEventListener('appinstalled', handleAppInstalled);
});
</script>

<template>
    <Transition name="slide-up">
        <div v-if="showPrompt" class="install-prompt">
            <div class="prompt-content">
                <div class="prompt-icon">
                    <i class="pi pi-download"></i>
                </div>
                <div class="prompt-text">
                    <p class="prompt-title">Install Zeen Console</p>
                    <p class="prompt-description">Get quick access from your home screen</p>
                </div>
            </div>
            <div class="prompt-actions">
                <Button
                    label="Install"
                    icon="pi pi-download"
                    size="small"
                    @click="install"
                    class="install-btn"
                />
                <button class="dismiss-btn" @click="dismiss" aria-label="Dismiss">
                    <i class="pi pi-times"></i>
                </button>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.install-prompt {
    position: fixed;
    bottom: 1rem;
    left: 1rem;
    right: 1rem;
    max-width: 420px;
    background: white;
    border-radius: 12px;
    padding: 1rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.15);
    z-index: 9999;
}

.prompt-content {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.prompt-icon {
    width: 40px;
    height: 40px;
    background: #106B4F15;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.prompt-icon i {
    font-size: 1.25rem;
    color: #106B4F;
}

.prompt-text {
    min-width: 0;
}

.prompt-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0;
}

.prompt-description {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0.125rem 0 0;
}

.prompt-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.install-btn {
    background: #106B4F !important;
    border-color: #106B4F !important;
}

.install-btn:hover {
    background: #0D5A42 !important;
    border-color: #0D5A42 !important;
}

.dismiss-btn {
    width: 32px;
    height: 32px;
    border: none;
    background: transparent;
    color: #9ca3af;
    cursor: pointer;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.15s;
}

.dismiss-btn:hover {
    background: #f3f4f6;
    color: #6b7280;
}

/* Transition */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    opacity: 0;
    transform: translateY(1rem);
}

/* Mobile adjustments */
@media (max-width: 480px) {
    .install-prompt {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }

    .prompt-actions {
        justify-content: flex-end;
    }
}

/* Desktop - position at bottom right */
@media (min-width: 768px) {
    .install-prompt {
        left: auto;
        right: 1.5rem;
        bottom: 1.5rem;
    }
}
</style>
