<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import register from '@/routes/register';

const plans = [
    {
        name: 'Free',
        price: '0',
        period: '/mo',
        description: 'Perfect for getting started and testing the platform',
        platformFee: '8%',
        features: [
            'Online booking page',
            'Basic client management',
            'Email notifications',
            'Calendar sync',
            'Compulsory deposit collection',
            '$500 JMD minimum deposit',
        ],
        cta: 'Get Started',
        ctaLink: register.provider.url(),
        highlighted: false,
    },
    {
        name: 'Premium',
        price: '3,500',
        period: '/mo',
        description: 'For growing businesses that need more features',
        platformFee: '4%',
        features: [
            'Everything in Free',
            'Custom branding & colors',
            '3 team members included',
            'Additional members $900/mo each',
            'Priority support',
            'Advanced analytics',
            'No-show protection',
        ],
        cta: 'Start Free Trial',
        ctaLink: register.provider.url({ query: { plan: 'premium' } }),
        highlighted: true,
        badge: 'Most Popular',
    },
    {
        name: 'Enterprise',
        price: '20,000',
        period: '/mo',
        description: 'For large teams with custom requirements',
        platformFee: '0%',
        feeNote: 'Transaction fees only',
        features: [
            'Everything in Premium',
            'Unlimited team members',
            'Custom templates',
            'API access',
            'Embeddable widgets',
            'Dedicated account manager',
            'Custom integrations',
        ],
        cta: 'Contact Sales',
        ctaLink: '/contact',
        highlighted: false,
    },
];

const faqs = [
    {
        question: 'What are transaction fees?',
        answer: 'Transaction fees are charged by our payment processor for each card payment. These fees are separate from our platform fees and typically range from 2.5-3.5% depending on the card type.',
    },
    {
        question: 'Can I change plans later?',
        answer: 'Yes! You can upgrade or downgrade your plan at any time. When upgrading, you\'ll get immediate access to new features. When downgrading, changes take effect at the start of your next billing cycle.',
    },
    {
        question: 'What is the minimum deposit requirement?',
        answer: 'On the Free tier, a minimum $500 JMD deposit is required for all bookings. This protects providers from no-shows and ensures clients are committed to their appointments.',
    },
    {
        question: 'How does team member pricing work?',
        answer: 'On Premium, 3 team members are included free. Additional team members beyond the first 3 cost $900 JMD each per month. Each team member gets their own login, calendar, and can manage their own bookings.',
    },
];
</script>

<template>
    <PublicLayout>
        <Head title="Pricing" />

        <div class="pricing-page">
            <!-- Hero -->
            <section class="pricing-hero">
                <span class="hero-badge">Simple Pricing</span>
                <h1>Choose the plan that fits your business</h1>
                <p>Start free and scale as you grow. All plans include core booking features.</p>
            </section>

            <!-- Pricing Cards -->
            <section class="pricing-cards">
                <div class="cards-container">
                    <div
                        v-for="plan in plans"
                        :key="plan.name"
                        class="pricing-card"
                        :class="{ highlighted: plan.highlighted }"
                    >
                        <span v-if="plan.badge" class="card-badge">{{ plan.badge }}</span>

                        <div class="card-header">
                            <h3>{{ plan.name }}</h3>
                            <p class="card-description">{{ plan.description }}</p>
                        </div>

                        <div class="card-price">
                            <span class="currency">$</span>
                            <span class="amount">{{ plan.price }}</span>
                            <span class="period">JMD{{ plan.period }}</span>
                        </div>

                        <div class="platform-fee">
                            <span class="fee-value">{{ plan.platformFee }}</span>
                            <span class="fee-label">platform fee</span>
                            <span v-if="plan.feeNote" class="fee-note">{{ plan.feeNote }}</span>
                        </div>

                        <Link :href="plan.ctaLink" class="card-cta" :class="{ primary: plan.highlighted }">
                            {{ plan.cta }}
                        </Link>

                        <ul class="features-list">
                            <li v-for="feature in plan.features" :key="feature">
                                <i class="pi pi-check"></i>
                                {{ feature }}
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <!-- FAQ Section -->
            <section class="faq-section">
                <h2>Frequently Asked Questions</h2>
                <div class="faq-grid">
                    <div v-for="faq in faqs" :key="faq.question" class="faq-item">
                        <h4>{{ faq.question }}</h4>
                        <p>{{ faq.answer }}</p>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="cta-section">
                <h2>Ready to grow your business?</h2>
                <p>Join thousands of service providers in Jamaica using Zeen.</p>
                <Link :href="register.provider.url()" class="cta-button">
                    Get Started for Free
                    <i class="pi pi-arrow-right"></i>
                </Link>
            </section>
        </div>
    </PublicLayout>
</template>

<style scoped>
.pricing-page {
    background: #fafbfc;
}

/* Hero Section */
.pricing-hero {
    text-align: center;
    padding: 4rem 2rem;
    background: linear-gradient(165deg, #f8faf9 0%, #e8f5f0 50%, #f0f4f8 100%);
}

.hero-badge {
    display: inline-block;
    background: #106B4F15;
    color: #106B4F;
    padding: 0.5rem 1rem;
    border-radius: 100px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.pricing-hero h1 {
    font-size: 2.75rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 1rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.pricing-hero p {
    font-size: 1.125rem;
    color: #4b5563;
    max-width: 500px;
    margin: 0 auto;
}

/* Pricing Cards */
.pricing-cards {
    padding: 0 2rem 5rem;
    margin-top: -2rem;
}

.cards-container {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.pricing-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    border: 1px solid #e5e7eb;
    position: relative;
    display: flex;
    flex-direction: column;
}

.pricing-card.highlighted {
    border-color: #106B4F;
    box-shadow: 0 8px 40px rgba(16, 107, 79, 0.15);
    transform: scale(1.02);
}

.card-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #106B4F;
    color: white;
    padding: 0.375rem 1rem;
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 600;
    white-space: nowrap;
}

.card-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    color: #0D1F1B;
    margin-bottom: 0.5rem;
}

.card-description {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 1.5rem;
}

.card-price {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
    margin-bottom: 0.5rem;
}

.currency {
    font-size: 1.25rem;
    font-weight: 600;
    color: #0D1F1B;
}

.amount {
    font-size: 3rem;
    font-weight: 700;
    color: #0D1F1B;
    line-height: 1;
}

.period {
    font-size: 0.875rem;
    color: #6b7280;
}

.platform-fee {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.fee-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: #106B4F;
}

.fee-label {
    font-size: 0.875rem;
    color: #6b7280;
}

.fee-note {
    font-size: 0.75rem;
    color: #9ca3af;
    width: 100%;
}

.card-cta {
    display: block;
    width: 100%;
    padding: 0.875rem;
    text-align: center;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.9375rem;
    text-decoration: none;
    transition: all 0.2s;
    margin-bottom: 1.5rem;
    background: #f3f4f6;
    color: #0D1F1B;
}

.card-cta:hover {
    background: #e5e7eb;
}

.card-cta.primary {
    background: #106B4F;
    color: white;
}

.card-cta.primary:hover {
    background: #0D5A42;
    box-shadow: 0 4px 12px rgba(16, 107, 79, 0.25);
}

.features-list {
    list-style: none;
    padding: 0;
    margin: 0;
    flex: 1;
}

.features-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.625rem 0;
    font-size: 0.875rem;
    color: #4b5563;
    border-top: 1px solid #f3f4f6;
}

.features-list li:first-child {
    border-top: none;
}

.features-list li i {
    color: #106B4F;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    flex-shrink: 0;
}

/* FAQ Section */
.faq-section {
    max-width: 900px;
    margin: 0 auto;
    padding: 4rem 2rem;
}

.faq-section h2 {
    text-align: center;
    font-size: 1.75rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 2.5rem;
}

.faq-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
}

.faq-item {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 1.5rem;
}

.faq-item h4 {
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
    margin-bottom: 0.75rem;
}

.faq-item p {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.6;
    margin: 0;
}

/* CTA Section */
.cta-section {
    text-align: center;
    padding: 4rem 2rem;
    background: linear-gradient(135deg, #106B4F, #0D5A42);
}

.cta-section h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
    margin-bottom: 0.5rem;
}

.cta-section p {
    font-size: 1rem;
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
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.2s;
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
}

/* Responsive */
@media (max-width: 1024px) {
    .cards-container {
        grid-template-columns: 1fr;
        max-width: 400px;
    }

    .pricing-card.highlighted {
        transform: none;
        order: -1;
    }
}

@media (max-width: 768px) {
    .pricing-hero {
        padding: 2rem 1rem;
    }

    .pricing-hero h1 {
        font-size: 2rem;
    }

    .pricing-cards {
        padding: 0 1rem 3rem;
    }

    .faq-grid {
        grid-template-columns: 1fr;
    }

    .faq-section {
        padding: 3rem 1rem;
    }

    .cta-section {
        padding: 3rem 1rem;
    }

    .cta-section h2 {
        font-size: 1.5rem;
    }
}
</style>
