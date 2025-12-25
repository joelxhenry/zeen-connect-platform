<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/components/layout/PublicLayout.vue';
import { foundingMembers } from '@/routes';

const audiences = [
    {
        title: 'Barbers & Grooming',
        tagline: 'Fill your chair, grow your rep',
        description:
            'From fades to beards, manage your appointments like a pro. Build a loyal clientele with automated reminders and showcase your portfolio.',
        benefits: ['Automated reminders', 'Client preferences', 'Portfolio showcase', 'Deposit protection'],
        image: 'https://images.unsplash.com/photo-1599351431202-1e0f0137899a?w=800&h=600&fit=crop',
        gradient: 'linear-gradient(135deg, #106B4F 0%, #1ABC9C 100%)',
        accent: '#1ABC9C',
    },
    {
        title: 'Beauty & Wellness',
        tagline: 'Your skills deserve recognition',
        description:
            'Nail techs, makeup artists, spa therapists—manage bookings professionally and let your work speak for itself.',
        benefits: ['Service menus', 'Duration scheduling', 'Client notes', 'Multiple services'],
        image: 'https://images.unsplash.com/photo-1560066984-138dadb4c035?w=800&h=600&fit=crop',
        gradient: 'linear-gradient(135deg, #E91E8C 0%, #FF6B9D 100%)',
        accent: '#E91E8C',
    },
    {
        title: 'Fitness & Training',
        tagline: 'Train harder, book smarter',
        description:
            'Personal trainers and fitness instructors can manage sessions, sell packages, and track client progress all in one place.',
        benefits: ['Session packages', 'Recurring bookings', 'Class scheduling', 'Progress tracking'],
        image: 'https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=800&h=600&fit=crop',
        gradient: 'linear-gradient(135deg, #F59E0B 0%, #FBBF24 100%)',
        accent: '#F59E0B',
    },
    {
        title: 'Photographers',
        tagline: 'Capture moments, not admin stress',
        description:
            'Book sessions, manage contracts, handle deposits, and deliver galleries—all while focusing on your creative vision.',
        benefits: ['Session packages', 'Contract management', 'Deposit handling', 'Delivery scheduling'],
        image: 'https://images.unsplash.com/photo-1554048612-b6a482bc67e5?w=800&h=600&fit=crop',
        gradient: 'linear-gradient(135deg, #8B5CF6 0%, #A78BFA 100%)',
        accent: '#8B5CF6',
    },
    {
        title: 'Home Services',
        tagline: 'Professional service, professional booking',
        description:
            'Plumbers, electricians, cleaners, and handymen can manage service areas, quotes, and team schedules effortlessly.',
        benefits: ['Service areas', 'Quote requests', 'Job scheduling', 'Team assignments'],
        image: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=800&h=600&fit=crop',
        gradient: 'linear-gradient(135deg, #3B82F6 0%, #60A5FA 100%)',
        accent: '#3B82F6',
    },
    {
        title: 'Consultants & Coaches',
        tagline: 'Share expertise, scale impact',
        description:
            'Business coaches, tutors, and consultants can offer their knowledge with seamless online booking and video integration.',
        benefits: ['Video integration', 'Session notes', 'Package offerings', 'Recurring sessions'],
        image: 'https://images.unsplash.com/photo-1552664730-d307ca884978?w=800&h=600&fit=crop',
        gradient: 'linear-gradient(135deg, #0D1F1B 0%, #106B4F 100%)',
        accent: '#106B4F',
    },
];

const stats = [
    { value: '24/7', label: 'Booking availability' },
    { value: '85%', label: 'Reduction in no-shows' },
    { value: '3hrs', label: 'Saved weekly on admin' },
];
</script>

<template>
    <PublicLayout>
        <Head title="For You - Who Zeen is Built For" />

        <div class="for-you-page">
            <!-- Hero Section -->
            <section class="hero">
                <div class="hero-background">
                    <div class="hero-blob blob-1"></div>
                    <div class="hero-blob blob-2"></div>
                    <div class="hero-blob blob-3"></div>
                </div>

                <div class="hero-content">
                    <div class="hero-text">
                        <span class="hero-badge">
                            <i class="pi pi-sparkles"></i>
                            Built for Jamaica's Service Pros
                        </span>
                        <h1>
                            <span class="text-line">Zeen is where</span>
                            <span class="text-line gradient-text">your business</span>
                            <span class="text-line">grows.</span>
                        </h1>
                        <p class="hero-description">
                            Whether you're cutting hair in Kingston, training clients in Montego Bay, or running a spa
                            in Ocho Rios—Zeen helps you book smarter, earn more, and stress less.
                        </p>

                        <div class="hero-cta">
                            <AppLink :href="foundingMembers.url()" class="btn-primary">
                                Join the Waitlist
                                <i class="pi pi-arrow-right"></i>
                            </AppLink>
                            <span class="cta-note">Free during beta • No credit card required</span>
                        </div>
                    </div>

                    <div class="hero-visual">
                        <div class="floating-card card-1">
                            <div class="card-avatar" style="background: #e91e8c">
                                <i class="pi pi-heart"></i>
                            </div>
                            <div class="card-info">
                                <span class="card-name">New Booking!</span>
                                <span class="card-detail">Manicure • 2:00 PM</span>
                            </div>
                        </div>

                        <div class="floating-card card-2">
                            <div class="card-avatar" style="background: #106b4f">
                                <i class="pi pi-check"></i>
                            </div>
                            <div class="card-info">
                                <span class="card-name">Payment Received</span>
                                <span class="card-detail">$2,500 JMD</span>
                            </div>
                        </div>

                        <div class="floating-card card-3">
                            <div class="card-avatar" style="background: #f59e0b">
                                <i class="pi pi-star-fill"></i>
                            </div>
                            <div class="card-info">
                                <span class="card-name">5-Star Review</span>
                                <span class="card-detail">"Amazing service!"</span>
                            </div>
                        </div>

                        <div class="phone-mockup">
                            <div class="phone-screen">
                                <div class="screen-header">
                                    <span class="screen-time">9:41</span>
                                </div>
                                <div class="screen-content">
                                    <div class="screen-greeting">Good morning, Marcus!</div>
                                    <div class="screen-stats">
                                        <div class="stat-item">
                                            <span class="stat-number">8</span>
                                            <span class="stat-label">Today</span>
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number">$45K</span>
                                            <span class="stat-label">This Month</span>
                                        </div>
                                    </div>
                                    <div class="screen-bookings">
                                        <div class="booking-item">
                                            <div class="booking-time">10:00</div>
                                            <div class="booking-info">
                                                <span>Andre Williams</span>
                                                <span class="booking-service">Fade + Beard</span>
                                            </div>
                                        </div>
                                        <div class="booking-item">
                                            <div class="booking-time">11:00</div>
                                            <div class="booking-info">
                                                <span>Kevin Brown</span>
                                                <span class="booking-service">Low Cut</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats Bar -->
            <section class="stats-bar">
                <div class="stats-container">
                    <div v-for="stat in stats" :key="stat.label" class="stat">
                        <span class="stat-value">{{ stat.value }}</span>
                        <span class="stat-label">{{ stat.label }}</span>
                    </div>
                </div>
            </section>

            <!-- Audiences Section -->
            <section class="audiences">
                <div class="section-header">
                    <span class="section-badge">Who We Serve</span>
                    <h2>Built for service professionals <span class="highlight">like you</span></h2>
                    <p>From barbers to photographers, coaches to cleaners—if you book appointments, Zeen is for you.</p>
                </div>

                <div class="audiences-grid">
                    <div
                        v-for="(audience, index) in audiences"
                        :key="audience.title"
                        class="audience-card"
                        :class="{ featured: index === 0 || index === 1 }"
                    >
                        <div class="card-image-wrapper">
                            <img :src="audience.image" :alt="audience.title" class="card-image" />
                            <div class="card-gradient" :style="{ background: audience.gradient }"></div>
                        </div>

                        <div class="card-body">
                            <h3>{{ audience.title }}</h3>
                            <p class="card-tagline">{{ audience.tagline }}</p>
                            <p class="card-description">{{ audience.description }}</p>

                            <div class="card-benefits">
                                <span
                                    v-for="benefit in audience.benefits"
                                    :key="benefit"
                                    class="benefit-tag"
                                    :style="{ '--accent': audience.accent }"
                                >
                                    <i class="pi pi-check-circle"></i>
                                    {{ benefit }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Open to All Section -->
            <section class="open-section">
                <div class="open-content">
                    <div class="open-visual">
                        <div class="visual-grid">
                            <div class="visual-icon"><i class="pi pi-car"></i></div>
                            <div class="visual-icon"><i class="pi pi-palette"></i></div>
                            <div class="visual-icon"><i class="pi pi-microphone"></i></div>
                            <div class="visual-icon"><i class="pi pi-book"></i></div>
                            <div class="visual-icon highlight"><i class="pi pi-plus"></i></div>
                            <div class="visual-icon"><i class="pi pi-wrench"></i></div>
                            <div class="visual-icon"><i class="pi pi-video"></i></div>
                            <div class="visual-icon"><i class="pi pi-graduation-cap"></i></div>
                            <div class="visual-icon"><i class="pi pi-briefcase"></i></div>
                        </div>
                    </div>
                    <div class="open-text">
                        <span class="section-badge">And Everyone Else</span>
                        <h2>Don't see your service? <span class="highlight">You're still welcome.</span></h2>
                        <p class="open-description">
                            DJs, event planners, tutors, mechanics, pet groomers, caterers, musicians,
                            tailors, tour guides—if you take bookings, Zeen works for you.
                        </p>
                        <ul class="open-benefits">
                            <li>
                                <i class="pi pi-check-circle"></i>
                                <span><strong>Flexible scheduling</strong> that adapts to any service type</span>
                            </li>
                            <li>
                                <i class="pi pi-check-circle"></i>
                                <span><strong>Custom service menus</strong> you design yourself</span>
                            </li>
                            <li>
                                <i class="pi pi-check-circle"></i>
                                <span><strong>Your own booking page</strong> to share anywhere</span>
                            </li>
                        </ul>
                        <AppLink :href="foundingMembers.url()" class="btn-secondary">
                            Tell Us Your Service
                            <i class="pi pi-arrow-right"></i>
                        </AppLink>
                    </div>
                </div>
            </section>

            <!-- Testimonials Placeholder -->
            <section class="testimonials">
                <div class="testimonials-bg">
                    <div class="testi-blob blob-1"></div>
                    <div class="testi-blob blob-2"></div>
                </div>

                <div class="testimonials-content">
                    <span class="section-badge light">Coming Soon</span>
                    <h2>Friendly Voices</h2>
                    <p class="section-desc">
                        Real stories from service providers across Jamaica who are growing their business with Zeen.
                    </p>

                    <div class="testimonial-preview">
                        <div class="preview-card">
                            <div class="preview-avatar">
                                <i class="pi pi-user"></i>
                            </div>
                            <div class="preview-content">
                                <div class="preview-quote">
                                    "Zeen changed how I run my barbershop. Clients book themselves, payments are
                                    automatic, and I can finally focus on cutting hair."
                                </div>
                                <div class="preview-author">
                                    <span class="author-name">Your Story Here</span>
                                    <span class="author-role">Be among our first success stories</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <AppLink :href="foundingMembers.url()" class="btn-outline-light">
                        Share Your Story
                        <i class="pi pi-arrow-right"></i>
                    </AppLink>
                </div>
            </section>

            <!-- Final CTA -->
            <section class="final-cta">
                <div class="cta-content">
                    <h2>Ready to level up your service business?</h2>
                    <p>
                        Join Jamaica's growing community of service professionals. Get early access, founding member
                        benefits, and help shape the platform.
                    </p>

                    <div class="cta-actions">
                        <AppLink :href="foundingMembers.url()" class="btn-cta">
                            Become a Founding Member
                            <i class="pi pi-arrow-right"></i>
                        </AppLink>
                    </div>

                    <div class="cta-features">
                        <span><i class="pi pi-check"></i> Free during beta</span>
                        <span><i class="pi pi-check"></i> Priority support</span>
                        <span><i class="pi pi-check"></i> Shape the product</span>
                    </div>
                </div>
            </section>
        </div>
    </PublicLayout>
</template>

<style scoped>
.for-you-page {
    background: #fafbfc;
    overflow-x: hidden;
}

/* Hero Section */
.hero {
    position: relative;
    min-height: 90vh;
    background: linear-gradient(165deg, #0d1f1b 0%, #0f2922 50%, #0d1f1b 100%);
    padding: 4rem 2rem;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    inset: 0;
    overflow: hidden;
}

.hero-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(80px);
    opacity: 0.4;
}

.blob-1 {
    width: 600px;
    height: 600px;
    background: #106b4f;
    top: -200px;
    right: -100px;
}

.blob-2 {
    width: 400px;
    height: 400px;
    background: #1abc9c;
    bottom: -100px;
    left: -100px;
}

.blob-3 {
    width: 300px;
    height: 300px;
    background: #f59e0b;
    top: 50%;
    left: 30%;
    opacity: 0.2;
}

.hero-content {
    position: relative;
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
    min-height: 70vh;
}

.hero-text {
    z-index: 1;
}

.hero-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: rgba(26, 188, 156, 0.15);
    color: #1abc9c;
    padding: 0.625rem 1.25rem;
    border-radius: 100px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 2rem;
    border: 1px solid rgba(26, 188, 156, 0.2);
}

.hero-badge i {
    font-size: 1rem;
}

.hero h1 {
    font-size: 4rem;
    font-weight: 800;
    line-height: 1.1;
    margin-bottom: 1.5rem;
}

.text-line {
    display: block;
    color: white;
}

.gradient-text {
    background: linear-gradient(135deg, #1abc9c 0%, #f59e0b 50%, #e91e8c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.hero-description {
    font-size: 1.25rem;
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.7;
    margin-bottom: 2.5rem;
    max-width: 500px;
}

.hero-cta {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: flex-start;
}

.btn-primary {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(135deg, #1abc9c 0%, #106b4f 100%);
    color: white;
    padding: 1rem 2rem;
    border-radius: 12px;
    font-size: 1.125rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(26, 188, 156, 0.3);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(26, 188, 156, 0.4);
}

.cta-note {
    font-size: 0.875rem;
    color: rgba(255, 255, 255, 0.5);
}

/* Hero Visual */
.hero-visual {
    position: relative;
    height: 500px;
}

.floating-card {
    position: absolute;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: white;
    padding: 0.875rem 1.25rem;
    border-radius: 16px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    z-index: 2;
    animation: float 6s ease-in-out infinite;
}

.card-1 {
    top: 10%;
    right: 10%;
    animation-delay: 0s;
}

.card-2 {
    top: 45%;
    right: 0;
    animation-delay: 2s;
}

.card-3 {
    bottom: 15%;
    right: 15%;
    animation-delay: 4s;
}

@keyframes float {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-10px);
    }
}

.card-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.card-info {
    display: flex;
    flex-direction: column;
}

.card-name {
    font-weight: 600;
    color: #0d1f1b;
    font-size: 0.9375rem;
}

.card-detail {
    font-size: 0.8125rem;
    color: #6b7280;
}

/* Phone Mockup */
.phone-mockup {
    position: absolute;
    left: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 280px;
    height: 560px;
    background: #0d1f1b;
    border-radius: 40px;
    padding: 12px;
    box-shadow: 0 25px 80px rgba(0, 0, 0, 0.4);
}

.phone-screen {
    width: 100%;
    height: 100%;
    background: linear-gradient(180deg, #106b4f 0%, #0d1f1b 100%);
    border-radius: 32px;
    overflow: hidden;
}

.screen-header {
    padding: 1rem;
    text-align: center;
}

.screen-time {
    color: white;
    font-weight: 600;
}

.screen-content {
    padding: 1rem 1.25rem;
}

.screen-greeting {
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
}

.screen-stats {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.stat-item {
    flex: 1;
    background: rgba(255, 255, 255, 0.1);
    padding: 1rem;
    border-radius: 12px;
    text-align: center;
}

.stat-number {
    display: block;
    color: #1abc9c;
    font-size: 1.5rem;
    font-weight: 700;
}

.stat-label {
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
}

.screen-bookings {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 12px;
    overflow: hidden;
}

.booking-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.875rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

.booking-item:last-child {
    border-bottom: none;
}

.booking-time {
    color: #1abc9c;
    font-weight: 600;
    font-size: 0.875rem;
}

.booking-info {
    display: flex;
    flex-direction: column;
}

.booking-info span:first-child {
    color: white;
    font-weight: 500;
    font-size: 0.875rem;
}

.booking-service {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.75rem;
}

/* Stats Bar */
.stats-bar {
    background: white;
    border-bottom: 1px solid #e5e7eb;
}

.stats-container {
    max-width: 800px;
    margin: 0 auto;
    display: flex;
    justify-content: center;
    gap: 4rem;
    padding: 2rem;
}

.stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 2rem;
    font-weight: 800;
    color: #106b4f;
}

.stat-label {
    color: #6b7280;
    font-size: 0.875rem;
}

/* Audiences Section */
.audiences {
    padding: 5rem 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.section-header {
    text-align: center;
    margin-bottom: 4rem;
}

.section-badge {
    display: inline-block;
    background: #106b4f15;
    color: #106b4f;
    padding: 0.5rem 1rem;
    border-radius: 100px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.section-badge.light {
    background: rgba(255, 255, 255, 0.15);
    color: white;
}

.section-header h2 {
    font-size: 2.5rem;
    font-weight: 800;
    color: #0d1f1b;
    margin-bottom: 1rem;
}

.section-header h2 .highlight {
    color: #106b4f;
}

.section-header p {
    font-size: 1.125rem;
    color: #6b7280;
    max-width: 600px;
    margin: 0 auto;
}

.audiences-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.5rem;
}

.audience-card {
    background: white;
    border-radius: 24px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.audience-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
}

.card-image-wrapper {
    position: relative;
    height: 200px;
    overflow: hidden;
}

.card-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.audience-card:hover .card-image {
    transform: scale(1.05);
}

.card-gradient {
    position: absolute;
    inset: 0;
    opacity: 0.7;
    mix-blend-mode: multiply;
}

.card-body {
    padding: 1.5rem;
}

.card-body h3 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #0d1f1b;
    margin-bottom: 0.25rem;
}

.card-tagline {
    color: #106b4f;
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.75rem;
}

.card-description {
    font-size: 0.9375rem;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 1.25rem;
}

.card-benefits {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.benefit-tag {
    display: inline-flex;
    align-items: center;
    gap: 0.375rem;
    background: #f3f4f6;
    padding: 0.375rem 0.75rem;
    border-radius: 100px;
    font-size: 0.75rem;
    color: #4b5563;
}

.benefit-tag i {
    color: var(--accent, #106b4f);
    font-size: 0.625rem;
}

/* Open to All Section */
.open-section {
    padding: 5rem 2rem;
    background: linear-gradient(135deg, #f8faf9 0%, #e8f5f0 50%, #f0f4f8 100%);
}

.open-content {
    max-width: 1100px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 4rem;
    align-items: center;
}

.open-visual {
    display: flex;
    justify-content: center;
}

.visual-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.visual-icon {
    width: 80px;
    height: 80px;
    background: white;
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    transition: all 0.3s ease;
}

.visual-icon:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
}

.visual-icon i {
    font-size: 1.75rem;
    color: #6b7280;
}

.visual-icon.highlight {
    background: linear-gradient(135deg, #1abc9c 0%, #106b4f 100%);
}

.visual-icon.highlight i {
    color: white;
}

.open-text .section-badge {
    margin-bottom: 1rem;
}

.open-text h2 {
    font-size: 2.25rem;
    font-weight: 800;
    color: #0d1f1b;
    margin-bottom: 1rem;
    line-height: 1.2;
}

.open-text h2 .highlight {
    color: #106b4f;
}

.open-description {
    font-size: 1.125rem;
    color: #6b7280;
    line-height: 1.7;
    margin-bottom: 1.5rem;
}

.open-benefits {
    list-style: none;
    padding: 0;
    margin: 0 0 2rem 0;
}

.open-benefits li {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.625rem 0;
    font-size: 1rem;
    color: #4b5563;
}

.open-benefits li i {
    color: #1abc9c;
    font-size: 1.25rem;
    margin-top: 0.125rem;
}

.open-benefits li strong {
    color: #0d1f1b;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: white;
    color: #106b4f;
    padding: 1rem 1.75rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    border: 2px solid #106b4f;
    transition: all 0.3s ease;
}

.btn-secondary:hover {
    background: #106b4f;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(16, 107, 79, 0.2);
}

/* Testimonials Section */
.testimonials {
    position: relative;
    background: linear-gradient(165deg, #0d1f1b 0%, #0f2922 100%);
    padding: 5rem 2rem;
    overflow: hidden;
}

.testimonials-bg {
    position: absolute;
    inset: 0;
}

.testi-blob {
    position: absolute;
    border-radius: 50%;
    filter: blur(100px);
    opacity: 0.3;
}

.testi-blob.blob-1 {
    width: 500px;
    height: 500px;
    background: #1abc9c;
    top: -200px;
    left: -100px;
}

.testi-blob.blob-2 {
    width: 400px;
    height: 400px;
    background: #f59e0b;
    bottom: -150px;
    right: -100px;
}

.testimonials-content {
    position: relative;
    max-width: 800px;
    margin: 0 auto;
    text-align: center;
}

.testimonials h2 {
    font-size: 2.5rem;
    font-weight: 800;
    color: white;
    margin-bottom: 1rem;
}

.section-desc {
    font-size: 1.125rem;
    color: rgba(255, 255, 255, 0.7);
    margin-bottom: 3rem;
}

.testimonial-preview {
    margin-bottom: 2.5rem;
}

.preview-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 20px;
    padding: 2rem;
    display: flex;
    align-items: flex-start;
    gap: 1.5rem;
    text-align: left;
}

.preview-avatar {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #1abc9c 0%, #106b4f 100%);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.preview-avatar i {
    font-size: 1.5rem;
    color: white;
}

.preview-content {
    flex: 1;
}

.preview-quote {
    font-size: 1.125rem;
    color: white;
    line-height: 1.7;
    margin-bottom: 1rem;
    font-style: italic;
}

.author-name {
    display: block;
    color: #1abc9c;
    font-weight: 600;
}

.author-role {
    color: rgba(255, 255, 255, 0.5);
    font-size: 0.875rem;
}

.btn-outline-light {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: white;
    border: 2px solid rgba(255, 255, 255, 0.3);
    padding: 0.875rem 1.75rem;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
}

.btn-outline-light:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
}

/* Final CTA */
.final-cta {
    padding: 6rem 2rem;
    background: linear-gradient(135deg, #f8faf9 0%, #e8f5f0 100%);
}

.cta-content {
    max-width: 700px;
    margin: 0 auto;
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    font-weight: 800;
    color: #0d1f1b;
    margin-bottom: 1rem;
}

.cta-content > p {
    font-size: 1.125rem;
    color: #6b7280;
    margin-bottom: 2.5rem;
    line-height: 1.7;
}

.cta-actions {
    margin-bottom: 2rem;
}

.btn-cta {
    display: inline-flex;
    align-items: center;
    gap: 0.75rem;
    background: linear-gradient(135deg, #106b4f 0%, #0d5a42 100%);
    color: white;
    padding: 1.125rem 2.5rem;
    border-radius: 14px;
    font-size: 1.125rem;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 4px 20px rgba(16, 107, 79, 0.3);
}

.btn-cta:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(16, 107, 79, 0.4);
}

.cta-features {
    display: flex;
    justify-content: center;
    gap: 2rem;
    flex-wrap: wrap;
}

.cta-features span {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #4b5563;
    font-size: 0.9375rem;
}

.cta-features i {
    color: #106b4f;
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-content {
        grid-template-columns: 1fr;
        text-align: center;
        gap: 3rem;
    }

    .hero-text {
        order: 1;
    }

    .hero-visual {
        order: 2;
        height: 400px;
        display: none;
    }

    .hero h1 {
        font-size: 3rem;
    }

    .hero-description {
        margin: 0 auto 2.5rem;
    }

    .hero-cta {
        align-items: center;
    }

    .audiences-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .open-content {
        grid-template-columns: 1fr;
        gap: 3rem;
        text-align: center;
    }

    .open-visual {
        order: 2;
    }

    .open-text {
        order: 1;
    }

    .open-benefits li {
        justify-content: center;
    }

    .open-text .section-badge {
        display: inline-block;
    }
}

@media (max-width: 768px) {
    .hero {
        padding: 3rem 1rem;
        min-height: auto;
    }

    .hero h1 {
        font-size: 2.25rem;
    }

    .hero-description {
        font-size: 1rem;
    }

    .stats-container {
        gap: 2rem;
        padding: 1.5rem 1rem;
    }

    .stat-value {
        font-size: 1.5rem;
    }

    .audiences {
        padding: 3rem 1rem;
    }

    .audiences-grid {
        grid-template-columns: 1fr;
        max-width: 400px;
        margin: 0 auto;
    }

    .section-header h2 {
        font-size: 1.75rem;
    }

    .open-section {
        padding: 3rem 1rem;
    }

    .open-text h2 {
        font-size: 1.5rem;
    }

    .open-description {
        font-size: 1rem;
    }

    .visual-grid {
        gap: 0.75rem;
    }

    .visual-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
    }

    .visual-icon i {
        font-size: 1.25rem;
    }

    .testimonials {
        padding: 3rem 1rem;
    }

    .testimonials h2 {
        font-size: 1.75rem;
    }

    .preview-card {
        flex-direction: column;
        text-align: center;
        align-items: center;
    }

    .preview-quote {
        text-align: center;
    }

    .final-cta {
        padding: 4rem 1rem;
    }

    .cta-content h2 {
        font-size: 1.75rem;
    }

    .cta-features {
        gap: 1rem;
    }

    .cta-features span {
        font-size: 0.8125rem;
    }
}
</style>
