<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import HeroSection from '@/components/marketing/HeroSection.vue';
import FeaturesSection from '@/components/marketing/FeaturesSection.vue';
import HowItWorksSection from '@/components/marketing/HowItWorksSection.vue';
import TestimonialsSection from '@/components/marketing/TestimonialsSection.vue';
import CtaSection from '@/components/marketing/CtaSection.vue';
</script>

<template>
    <PublicLayout>
        <Head title="Grow Your Service Business" />

        <div class="welcome-page">
            <HeroSection />
            <FeaturesSection />
            <HowItWorksSection />
            <TestimonialsSection />
            <CtaSection />
        </div>
    </PublicLayout>
</template>

<style scoped>
.welcome-page {
    width: 100%;
}
</style>
