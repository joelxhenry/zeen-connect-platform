<script setup lang="ts">
const features = [
    {
        icon: 'pi-calendar',
        title: 'Online Booking System',
        description: 'Let clients book your services 24/7, even while you sleep.',
        accent: false,
    },
    {
        icon: 'pi-users',
        title: 'Client Management',
        description: 'Track appointments, client history, and preferences in one place.',
        accent: true,
    },
    {
        icon: 'pi-credit-card',
        title: 'Payment Processing',
        description: 'Accept payments seamlessly with secure payment integration.',
        accent: false,
    },
    {
        icon: 'pi-palette',
        title: 'Your Own Brand',
        description: 'Customize your site with your logo, colors, and unique style.',
        accent: true,
    },
    {
        icon: 'pi-search',
        title: 'Marketing Tools',
        description: 'Get discovered by new clients searching for your services.',
        accent: false,
    },
    {
        icon: 'pi-mobile',
        title: 'Mobile Friendly',
        description: 'Manage your business on the go from any device.',
        accent: true,
    },
];
</script>

<template>
    <section class="features-section">
        <div class="features-background"></div>
        <div class="features-container">
            <div class="section-header">
                <span class="section-badge">Why Choose Zeen</span>
                <h2>Everything You Need to Succeed</h2>
                <p>Powerful tools designed specifically for Jamaican service providers.</p>
            </div>
            <div class="features-grid">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="feature-card"
                >
                    <div :class="['feature-icon', { accent: feature.accent }]">
                        <i :class="['pi', feature.icon]"></i>
                    </div>
                    <h3>{{ feature.title }}</h3>
                    <p>{{ feature.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.features-section {
    position: relative;
    padding: 5rem 2rem;
    background: white;
    overflow: hidden;
}

.features-background {
    position: absolute;
    inset: 0;
    background-image: url('https://images.unsplash.com/photo-1497366216548-37526070297c?w=1920&q=80');
    background-size: cover;
    background-position: center;
    opacity: 0.03;
}

.features-container {
    position: relative;
    z-index: 1;
}

.section-header {
    text-align: center;
    max-width: 600px;
    margin: 0 auto 3rem;
}

.section-badge {
    display: inline-block;
    background: #106B4F15;
    color: #106B4F;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.section-header h2 {
    font-size: 2.25rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 0.75rem;
}

.section-header p {
    font-size: 1.125rem;
    color: #6b7280;
}

.features-grid {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.feature-card {
    background: #f8faf9;
    padding: 2rem;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.feature-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
}

.feature-icon {
    width: 56px;
    height: 56px;
    background: #106B4F;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1.25rem;
}

.feature-icon.accent {
    background: #1ABC9C;
}

.feature-icon i {
    font-size: 1.5rem;
    color: white;
}

.feature-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #0D1F1B;
    margin-bottom: 0.75rem;
}

.feature-card p {
    color: #6b7280;
    line-height: 1.6;
}

/* Mobile Responsive */
@media (max-width: 1024px) {
    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .features-section {
        padding: 3rem 1rem;
    }

    .features-grid {
        grid-template-columns: 1fr;
    }

    .section-header h2 {
        font-size: 1.75rem;
    }
}
</style>
