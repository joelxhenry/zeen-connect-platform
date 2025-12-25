<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import register from '@/routes/register';
</script>

<template>
    <section class="cta-section">
        <div class="cta-background"></div>
        <div class="cta-content">
            <h2>Ready to Grow Your Business?</h2>
            <p>Join thousands of service providers already thriving on Zeen Connect.</p>
            <Link :href="register.provider.url()" class="btn btn-cta">
                Start Your Free Account
                <i class="pi pi-arrow-right"></i>
            </Link>
            <span class="cta-note">No credit card required &bull; Free to get started</span>
        </div>
    </section>
</template>

<style scoped>
.cta-section {
    position: relative;
    padding: 5rem 2rem;
    background: linear-gradient(135deg, #106B4F 0%, #0D5A42 100%);
    overflow: hidden;
}

.cta-background {
    position: absolute;
    inset: 0;
    background-image: url('https://images.unsplash.com/photo-1521791136064-7986c2920216?w=1920&q=80');
    background-size: cover;
    background-position: center;
    opacity: 0.1;
}

.cta-content {
    position: relative;
    z-index: 1;
    max-width: 700px;
    margin: 0 auto;
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    font-weight: 700;
    color: white;
    margin-bottom: 1rem;
}

.cta-content p {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.85);
    margin-bottom: 2rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1.75rem;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn-cta {
    background: white;
    color: #106B4F;
    padding: 1rem 2rem;
    font-size: 1.125rem;
}

.btn-cta:hover {
    background: #f8faf9;
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
}

.cta-note {
    display: block;
    margin-top: 1rem;
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.875rem;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .cta-section {
        padding: 3rem 1rem;
    }

    .cta-content h2 {
        font-size: 1.75rem;
    }

    .btn-cta {
        width: 100%;
        justify-content: center;
    }
}
</style>
