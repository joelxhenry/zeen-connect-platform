<script setup lang="ts">
const emit = defineEmits<{
    (e: 'join'): void;
}>();

const tiers = [
    {
        name: 'Growth Founders',
        badge: 'growth',
        perks: [
            {
                icon: 'pi-lock',
                title: 'Lifetime Price Lock',
                description: 'Pay $3,400/mo forever, even as we add new features.',
            },
            {
                icon: 'pi-percentage',
                title: 'Waived Zeen Fees',
                description: '0% Zeen fee for your first 3 months.',
            },
            {
                icon: 'pi-user',
                title: 'VIP Onboarding',
                description: 'Personal setup assistance for your first 10 services.',
            },
        ],
        cta: 'Join as Growth Founder',
    },
    {
        name: 'Enterprise Founders',
        badge: 'enterprise',
        perks: [
            {
                icon: 'pi-lock',
                title: 'Lifetime Price Lock',
                description: 'Pay $12,000/mo forever, locked in as a founding partner.',
            },
            {
                icon: 'pi-percentage',
                title: 'Waived Zeen Fees',
                description: '0% Zeen fee for your first 6 months.',
            },
            {
                icon: 'pi-star',
                title: 'Priority Feature Requests',
                description: 'Direct input on our product roadmap and early access to new features.',
            },
        ],
        cta: 'Join as Enterprise Founder',
        isEnterprise: true,
    },
];

const scrollToForm = () => {
    emit('join');
};
</script>

<template>
    <section class="perks">
        <div class="perks-container">
            <span class="section-label">Exclusive Perks</span>
            <h2>What founding members get</h2>
            <p class="section-subtitle">Early adopters get exclusive benefits that last forever.</p>

            <div class="tiers-grid">
                <div v-for="tier in tiers" :key="tier.name" class="tier-card">
                    <h3>{{ tier.name }}</h3>

                    <div class="perks-list">
                        <div v-for="perk in tier.perks" :key="perk.title" class="perk-item">
                            <div class="perk-icon">
                                <i :class="['pi', perk.icon]"></i>
                            </div>
                            <div class="perk-content">
                                <h4>{{ perk.title }}</h4>
                                <p>{{ perk.description }}</p>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="tier-cta"
                        :class="{ enterprise: tier.isEnterprise }"
                        @click="scrollToForm"
                    >
                        {{ tier.cta }}
                        <i class="pi pi-arrow-up"></i>
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.perks {
    padding: 5rem 2rem;
    background: linear-gradient(135deg, #0D1F1B, #1a3d35);
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
    color: white;
    margin-bottom: 0.75rem;
}

.section-subtitle {
    font-size: 1.0625rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 2.5rem;
}

.tiers-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 2rem;
}

.tier-card {
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    text-align: left;
}

.tier-card h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #1ABC9C;
    margin-bottom: 1.5rem;
    text-align: center;
}

.perks-list {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    margin-bottom: 2rem;
}

.perk-item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
}

.perk-icon {
    width: 40px;
    height: 40px;
    background: rgba(26, 188, 156, 0.15);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    color: #1ABC9C;
    font-size: 1rem;
}

.perk-content h4 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: white;
    margin-bottom: 0.25rem;
}

.perk-content p {
    font-size: 0.8125rem;
    color: rgba(255, 255, 255, 0.6);
    line-height: 1.5;
    margin: 0;
}

.tier-cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    background: #1ABC9C;
    color: #0D1F1B;
    padding: 0.875rem 1.5rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9375rem;
    cursor: pointer;
    transition: all 0.2s;
}

.tier-cta:hover {
    background: #16a085;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(26, 188, 156, 0.3);
}

.tier-cta.enterprise {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.tier-cta.enterprise:hover {
    background: rgba(255, 255, 255, 0.2);
}

@media (max-width: 768px) {
    .perks {
        padding: 3rem 1rem;
    }

    .perks h2 {
        font-size: 1.5rem;
    }

    .tiers-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    .tier-card {
        padding: 1.5rem;
    }
}
</style>
