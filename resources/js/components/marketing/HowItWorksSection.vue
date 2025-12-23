<script setup lang="ts">
const steps = [
    {
        number: '01',
        icon: 'pi-user-plus',
        title: 'Sign Up',
        description: 'Create your provider profile in minutes. Add your business details, services, and photos.',
    },
    {
        number: '02',
        icon: 'pi-cog',
        title: 'Set Up Services',
        description: 'Define your service offerings, set prices, and configure your availability schedule.',
    },
    {
        number: '03',
        icon: 'pi-wallet',
        title: 'Start Earning',
        description: 'Receive bookings from clients and grow your business with Zeen Connect.',
    },
];
</script>

<template>
    <section id="how-it-works" class="how-it-works-section">
        <div class="section-header">
            <span class="section-badge">How It Works</span>
            <h2>Get Started in 3 Simple Steps</h2>
            <p>Setting up your business on Zeen is quick and easy.</p>
        </div>
        <div class="steps-container">
            <div v-for="(step, index) in steps" :key="step.number" class="step-card">
                <div class="step-icon">
                    <i :class="['pi', step.icon]"></i>
                </div>
                <div class="step-number">{{ step.number }}</div>
                <h3>{{ step.title }}</h3>
                <p>{{ step.description }}</p>
                <div v-if="index < steps.length - 1" class="step-connector">
                    <i class="pi pi-arrow-right"></i>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.how-it-works-section {
    padding: 5rem 2rem;
    background: #0D1F1B;
}

.section-header {
    text-align: center;
    max-width: 600px;
    margin: 0 auto 3rem;
}

.section-badge {
    display: inline-block;
    background: #1ABC9C30;
    color: #1ABC9C;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.section-header h2 {
    font-size: 2.25rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.75rem;
}

.section-header p {
    font-size: 1.125rem;
    color: #9ca3af;
}

.steps-container {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.step-card {
    position: relative;
    text-align: center;
    padding: 1.5rem;
}

.step-icon {
    width: 80px;
    height: 80px;
    background: #1ABC9C20;
    border: 2px solid #1ABC9C;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
}

.step-icon i {
    font-size: 2rem;
    color: #1ABC9C;
}

.step-number {
    font-size: 1.25rem;
    font-weight: 700;
    color: #1ABC9C;
    margin-bottom: 0.5rem;
}

.step-card h3 {
    font-size: 1.5rem;
    font-weight: 600;
    color: white;
    margin-bottom: 0.75rem;
}

.step-card p {
    color: #9ca3af;
    line-height: 1.6;
}

.step-connector {
    position: absolute;
    right: -1.5rem;
    top: 50%;
    transform: translateY(-50%);
    color: #1ABC9C;
    font-size: 1.5rem;
    display: none;
}

/* Mobile Responsive */
@media (max-width: 1024px) {
    .steps-container {
        grid-template-columns: 1fr;
        gap: 2rem;
        max-width: 500px;
    }

    .step-connector {
        display: none;
    }
}

@media (max-width: 768px) {
    .how-it-works-section {
        padding: 3rem 1rem;
    }

    .section-header h2 {
        font-size: 1.75rem;
    }

    .step-icon {
        width: 70px;
        height: 70px;
    }

    .step-icon i {
        font-size: 1.75rem;
    }
}

@media (min-width: 1025px) {
    .step-connector {
        display: block;
    }
}
</style>
