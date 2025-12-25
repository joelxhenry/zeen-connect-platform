<script setup lang="ts">
const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: 'smooth' });
};
</script>

<template>
    <section class="final-cta">
        <div class="cta-container">
            <h2>Ready to be a pioneer?</h2>
            <p>Secure your spot and help shape the future of Jamaican services.</p>
            <a href="#" class="cta-button" @click.prevent="scrollToTop">
                Claim Your Founding Spot
                <i class="pi pi-arrow-up"></i>
            </a>
        </div>
    </section>
</template>

<style scoped>
.final-cta {
    padding: 5rem 2rem;
    background: linear-gradient(135deg, #106B4F, #0D5A42);
}

.cta-container {
    max-width: 600px;
    margin: 0 auto;
    text-align: center;
}

.final-cta h2 {
    font-size: 2rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.75rem;
}

.final-cta p {
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.8);
    margin-bottom: 2rem;
}

.cta-button {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    color: #106B4F;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

@media (max-width: 640px) {
    .final-cta { padding: 3rem 1rem; }
    .final-cta h2 { font-size: 1.5rem; }
}
</style>
