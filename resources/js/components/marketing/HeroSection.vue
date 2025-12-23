<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <section class="hero-section">
        <div class="hero-content">
            <div class="hero-text">
                <span class="hero-badge">For Service Providers</span>
                <h1>Grow Your Service Business with <span class="highlight">Zeen</span></h1>
                <p class="tagline">CLICK. BOOK. ZEEN.</p>
                <p class="hero-description">
                    Join Jamaica's leading platform for service professionals.
                    Manage bookings, reach new clients, and grow your business — all in one place.
                </p>
                <div class="hero-actions">
                    <Link href="/register/provider" class="btn btn-primary">
                        Join as Provider
                        <i class="pi pi-arrow-right"></i>
                    </Link>
                    <a href="#how-it-works" class="btn btn-secondary">
                        Learn More
                    </a>
                </div>
            </div>
            <div class="hero-visual">
                <div class="image-wrapper">
                    <img
                        src="https://images.unsplash.com/photo-1560066984-138dadb4c035?w=600&h=750&fit=crop"
                        alt="Service provider"
                        class="main-image"
                    />
                </div>
                <div class="floating-card card-1">
                    <img
                        src="https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=200&h=200&fit=crop"
                        alt="Fitness"
                    />
                </div>
                <div class="floating-card card-2">
                    <img
                        src="https://images.unsplash.com/photo-1554048612-b6a482bc67e5?w=200&h=200&fit=crop"
                        alt="Photography"
                    />
                </div>
                <div class="accent-shape shape-1"></div>
                <div class="accent-shape shape-2"></div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero-section {
    padding: 5rem 2rem;
    background: linear-gradient(165deg, #f8faf9 0%, #e8f5f0 50%, #f0f4f8 100%);
    overflow: hidden;
}

.hero-content {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

.hero-badge {
    display: inline-block;
    background: #106B4F15;
    color: #106B4F;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.hero-text h1 {
    font-size: 3.25rem;
    font-weight: 700;
    color: #0D1F1B;
    line-height: 1.15;
    margin-bottom: 1rem;
}

.hero-text h1 .highlight {
    color: #106B4F;
}

.hero-text .tagline {
    font-size: 1.125rem;
    font-weight: 600;
    color: #1ABC9C;
    letter-spacing: 0.2em;
    margin-bottom: 1.25rem;
}

.hero-description {
    font-size: 1.125rem;
    color: #4b5563;
    line-height: 1.7;
    margin-bottom: 2rem;
    max-width: 480px;
}

.hero-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Visual Section */
.hero-visual {
    position: relative;
    height: 520px;
}

.image-wrapper {
    position: absolute;
    top: 0;
    right: 0;
    width: 380px;
    height: 480px;
    border-radius: 200px 200px 40px 40px;
    overflow: hidden;
    box-shadow: 0 30px 60px -15px rgba(16, 107, 79, 0.2);
}

.main-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.floating-card {
    position: absolute;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
}

.floating-card img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.card-1 {
    width: 140px;
    height: 140px;
    bottom: 80px;
    left: 0;
    border-radius: 24px;
}

.card-2 {
    width: 120px;
    height: 120px;
    top: 60px;
    left: 60px;
    border-radius: 50%;
}

/* Accent Shapes */
.accent-shape {
    position: absolute;
    border-radius: 50%;
}

.shape-1 {
    width: 80px;
    height: 80px;
    background: #106B4F15;
    bottom: 20px;
    left: 120px;
}

.shape-2 {
    width: 40px;
    height: 40px;
    background: #1ABC9C30;
    top: 20px;
    left: 20px;
}

/* Buttons */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem 1.75rem;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s ease;
    cursor: pointer;
}

.btn-primary {
    background: #106B4F;
    color: white;
}

.btn-primary:hover {
    background: #0D5A42;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 107, 79, 0.25);
}

.btn-secondary {
    background: transparent;
    color: #0D1F1B;
    border: 2px solid #d1d5db;
}

.btn-secondary:hover {
    border-color: #0D1F1B;
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-content {
        grid-template-columns: 1fr;
        gap: 3rem;
    }

    .hero-visual {
        order: -1;
        height: 420px;
        max-width: 400px;
        margin: 0 auto;
    }

    .image-wrapper {
        width: 280px;
        height: 360px;
        right: 20px;
    }

    .card-1 {
        width: 110px;
        height: 110px;
        bottom: 60px;
        left: 0;
    }

    .card-2 {
        width: 90px;
        height: 90px;
        top: 40px;
        left: 40px;
    }

    .hero-text {
        text-align: center;
    }

    .hero-description {
        margin-left: auto;
        margin-right: auto;
    }

    .hero-actions {
        justify-content: center;
    }
}

@media (max-width: 768px) {
    .hero-section {
        padding: 3rem 1rem;
    }

    .hero-text h1 {
        font-size: 2.25rem;
    }

    .hero-description {
        font-size: 1rem;
    }

    .hero-visual {
        height: 350px;
    }

    .image-wrapper {
        width: 220px;
        height: 280px;
        right: 30px;
    }

    .card-1 {
        width: 90px;
        height: 90px;
        bottom: 50px;
    }

    .card-2 {
        width: 70px;
        height: 70px;
        top: 30px;
        left: 20px;
    }

    .shape-1, .shape-2 {
        display: none;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .hero-actions {
        flex-direction: column;
    }
}
</style>
