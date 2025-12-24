<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import MadeInJamaica from '@/components/common/MadeInJamaica.vue';

interface Props {
    copyrightName?: string;
    showPoweredBy?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    copyrightName: 'Zeen',
    showPoweredBy: false,
});

const page = usePage();
const mainPlatformUrl = (page.props.mainPlatformUrl as string) || '';
const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="site-footer">
        <div class="footer-content">
            <div class="footer-left">
                <p class="copyright">
                    &copy; {{ currentYear }} {{ copyrightName }}. All rights reserved.
                </p>
                <a v-if="showPoweredBy" :href="mainPlatformUrl" class="powered-by">
                    Powered by <span class="brand">Zeen</span>
                </a>
            </div>
            <div class="footer-right">
                <MadeInJamaica />
                <div class="footer-links">
                    <a :href="showPoweredBy ? `${mainPlatformUrl}/privacy` : '/privacy'">Privacy Policy</a>
                    <a :href="showPoweredBy ? `${mainPlatformUrl}/terms` : '/terms'">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>
.site-footer {
    background-color: white;
    border-top: 1px solid #e5e7eb;
    padding: 1.5rem 0;
}

.footer-content {
    max-width: 1280px;
    margin: 0 auto;
    padding: 0 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.footer-left {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.copyright {
    margin: 0;
    color: #6b7280;
    font-size: 0.875rem;
}

.powered-by {
    font-size: 0.75rem;
    color: #9ca3af;
    text-decoration: none;
    transition: color 0.15s;
}

.powered-by:hover {
    color: #6b7280;
}

.powered-by .brand {
    color: #106B4F;
    font-weight: 600;
}

.footer-right {
    display: flex;
    align-items: center;
    gap: 2rem;
}

.footer-links {
    display: flex;
    gap: 1.5rem;
}

.footer-links a {
    color: #6b7280;
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.15s;
}

.footer-links a:hover {
    color: #0D1F1B;
}

@media (max-width: 640px) {
    .footer-content {
        flex-direction: column;
        text-align: center;
    }

    .footer-left {
        align-items: center;
    }

    .footer-right {
        flex-direction: column;
        gap: 1rem;
    }

    .footer-links {
        gap: 1rem;
    }
}
</style>
