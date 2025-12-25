<script setup lang="ts">
const perks = [
    {
        icon: 'pi-verified',
        title: 'Founding Member Badge',
        description: 'Exclusive badge displayed on your profile forever.',
    },
    {
        icon: 'pi-lock',
        title: '15% Price Lock',
        description: 'Lock in 15% off all subscription plans for life.',
    },
    {
        icon: 'pi-star',
        title: 'Priority Support',
        description: 'Jump to the front of the queue for help.',
    },
    {
        icon: 'pi-bolt',
        title: 'Early Access',
        description: 'Be first to try new features before anyone else.',
    },
];
</script>

<template>
    <section class="perks">
        <div class="perks-container">
            <span class="section-label">Exclusive Perks</span>
            <h2>What founding members get</h2>

            <div class="perks-grid">
                <div v-for="perk in perks" :key="perk.title" class="perk-card">
                    <div class="perk-icon">
                        <i :class="['pi', perk.icon]"></i>
                    </div>
                    <h3>{{ perk.title }}</h3>
                    <p>{{ perk.description }}</p>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.perks {
    padding: 5rem 2rem;
    background: white;
}

.perks-container {
    max-width: 1000px;
    margin: 0 auto;
    text-align: center;
}

.section-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: #1ABC9C;
    margin-bottom: 0.75rem;
}

.perks h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 2.5rem;
}

.perks-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.5rem;
}

.perk-card {
    background: #f8faf9;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.25s;
}

.perk-card:hover {
    border-color: #106B4F30;
    box-shadow: 0 8px 24px rgba(16, 107, 79, 0.08);
    transform: translateY(-2px);
}

.perk-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #106B4F15, #1ABC9C15);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1rem;
    color: #106B4F;
    font-size: 1.25rem;
}

.perk-card h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
    margin-bottom: 0.5rem;
}

.perk-card p {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

@media (max-width: 1024px) {
    .perks-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 640px) {
    .perks { padding: 3rem 1rem; }
    .perks h2 { font-size: 1.5rem; }
    .perks-grid { grid-template-columns: 1fr; }
}
</style>
