<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';
import Divider from 'primevue/divider';
</script>

<template>
    <Head title="Welcome" />
    <div class="welcome-page min-h-screen">
        <!-- Header -->
        <header class="header fixed top-0 left-0 right-0 z-50 backdrop-blur-md border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <Link href="/" class="brand-text text-2xl font-bold">
                            Zeen
                        </Link>
                    </div>
                    <nav class="hidden md:flex items-center space-x-8">
                        <Link href="/pricing" class="nav-link">
                            Pricing
                        </Link>
                        <Link href="/how-it-works" class="nav-link">
                            How It Works
                        </Link>
                    </nav>
                    <div class="flex items-center space-x-4">
                        <Link href="/login">
                            <Button label="Sign In" text />
                        </Link>
                        <Link href="/register/provider">
                            <Button label="Start Free Trial" />
                        </Link>
                    </div>
                </div>
            </div>
        </header>

        <!-- Hero Section - Provider Focused -->
        <section class="hero-section pt-32 pb-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto text-center">
                <Tag severity="success" class="mb-6">Now Available in Jamaica</Tag>
                <h1 class="hero-title text-4xl sm:text-5xl lg:text-6xl font-extrabold mb-6">
                    Grow Your Service Business
                    <span class="brand-text block">With Zeen</span>
                </h1>
                <p class="hero-subtitle text-xl mb-10 max-w-2xl mx-auto">
                    The all-in-one platform for service professionals. Manage bookings, accept payments, and build your client base across Jamaica.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4 mb-12">
                    <Link href="/register/provider">
                        <Button size="large" class="px-8">
                            <i class="pi pi-shop mr-2"></i>
                            Get Started Free
                        </Button>
                    </Link>
                    <Link href="/how-it-works">
                        <Button severity="secondary" outlined size="large" class="px-8">
                            <i class="pi pi-play mr-2"></i>
                            See How It Works
                        </Button>
                    </Link>
                </div>

                <!-- Stats - Provider Focused -->
                <div class="grid grid-cols-3 gap-8 max-w-3xl mx-auto">
                    <div class="text-center">
                        <div class="stat-value text-3xl font-bold">500+</div>
                        <div class="stat-label text-sm">Active Providers</div>
                    </div>
                    <div class="text-center">
                        <div class="stat-value text-3xl font-bold">54+</div>
                        <div class="stat-label text-sm">Service Areas</div>
                    </div>
                    <div class="text-center">
                        <div class="stat-value text-3xl font-bold">$2M+</div>
                        <div class="stat-label text-sm">Monthly Revenue</div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Provider Benefits Section -->
        <section class="section-alt py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="section-title text-3xl font-bold mb-4">
                        Everything You Need to Succeed
                    </h2>
                    <p class="section-subtitle">
                        Tools designed specifically for service professionals in Jamaica
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <Card class="feature-card">
                        <template #title>
                            <div class="flex items-center">
                                <i class="pi pi-calendar text-2xl icon-primary mr-3"></i>
                                <span class="font-semibold">Smart Scheduling</span>
                            </div>
                        </template>
                        <template #content>
                            <p class="card-text">
                                Let clients book 24/7. Set your availability, service duration, and buffer times. Automatic reminders reduce no-shows.
                            </p>
                        </template>
                    </Card>

                    <Card class="feature-card">
                        <template #title>
                            <div class="flex items-center">
                                <i class="pi pi-wallet text-2xl icon-primary mr-3"></i>
                                <span class="font-semibold">Accept Payments</span>
                            </div>
                        </template>
                        <template #content>
                            <p class="card-text">
                                Get paid instantly via Power Tranz. Accept credit cards and local payment methods. Direct deposits to your bank account.
                            </p>
                        </template>
                    </Card>

                    <Card class="feature-card">
                        <template #title>
                            <div class="flex items-center">
                                <i class="pi pi-map-marker text-2xl icon-primary mr-3"></i>
                                <span class="font-semibold">Multi-Location Support</span>
                            </div>
                        </template>
                        <template #content>
                            <p class="card-text">
                                Serve multiple parishes and towns. Perfect for mobile barbers, beauticians, and service professionals who travel.
                            </p>
                        </template>
                    </Card>

                    <Card class="feature-card">
                        <template #title>
                            <div class="flex items-center">
                                <i class="pi pi-images text-2xl icon-primary mr-3"></i>
                                <span class="font-semibold">Portfolio Showcase</span>
                            </div>
                        </template>
                        <template #content>
                            <p class="card-text">
                                Display your best work with a professional portfolio. Upload photos, highlight certifications, and attract new clients.
                            </p>
                        </template>
                    </Card>

                    <Card class="feature-card">
                        <template #title>
                            <div class="flex items-center">
                                <i class="pi pi-star text-2xl icon-primary mr-3"></i>
                                <span class="font-semibold">Build Your Reputation</span>
                            </div>
                        </template>
                        <template #content>
                            <p class="card-text">
                                Collect verified reviews from real clients. Higher ratings mean more visibility and more bookings.
                            </p>
                        </template>
                    </Card>

                    <Card class="feature-card">
                        <template #title>
                            <div class="flex items-center">
                                <i class="pi pi-chart-line text-2xl icon-primary mr-3"></i>
                                <span class="font-semibold">Business Insights</span>
                            </div>
                        </template>
                        <template #content>
                            <p class="card-text">
                                Track your earnings, busiest times, and top services. Make data-driven decisions to grow your business.
                            </p>
                        </template>
                    </Card>
                </div>
            </div>
        </section>

        <!-- Service Categories Section -->
        <section class="py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="section-title text-3xl font-bold mb-4">
                        Built for Service Professionals
                    </h2>
                    <p class="section-subtitle">
                        Join hundreds of professionals already growing their business on Zeen
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <Card class="category-card text-center cursor-pointer">
                        <template #content>
                            <i class="pi pi-user text-4xl category-icon-1 mb-2"></i>
                            <p class="font-medium">Barbers</p>
                        </template>
                    </Card>

                    <Card class="category-card text-center cursor-pointer">
                        <template #content>
                            <i class="pi pi-user text-4xl category-icon-2 mb-2"></i>
                            <p class="font-medium">Hair Stylists</p>
                        </template>
                    </Card>

                    <Card class="category-card text-center cursor-pointer">
                        <template #content>
                            <i class="pi pi-palette text-4xl category-icon-3 mb-2"></i>
                            <p class="font-medium">Makeup Artists</p>
                        </template>
                    </Card>

                    <Card class="category-card text-center cursor-pointer">
                        <template #content>
                            <i class="pi pi-star text-4xl category-icon-4 mb-2"></i>
                            <p class="font-medium">Nail Techs</p>
                        </template>
                    </Card>

                    <Card class="category-card text-center cursor-pointer">
                        <template #content>
                            <i class="pi pi-heart text-4xl category-icon-5 mb-2"></i>
                            <p class="font-medium">Massage Therapists</p>
                        </template>
                    </Card>

                    <Card class="category-card text-center cursor-pointer">
                        <template #content>
                            <i class="pi pi-ellipsis-h text-4xl category-icon-6 mb-2"></i>
                            <p class="font-medium">And More...</p>
                        </template>
                    </Card>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section class="section-alt py-20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="section-title text-3xl font-bold mb-4">
                        Start in Minutes
                    </h2>
                    <p class="section-subtitle">
                        Get your business online in three simple steps
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-8 max-w-4xl mx-auto">
                    <div class="text-center">
                        <div class="step-circle w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center">
                            <span class="text-2xl font-bold">1</span>
                        </div>
                        <h3 class="section-title text-lg font-semibold mb-2">Create Your Profile</h3>
                        <p class="section-subtitle">
                            Sign up free, add your services, pricing, and upload your portfolio photos.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="step-circle w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center">
                            <span class="text-2xl font-bold">2</span>
                        </div>
                        <h3 class="section-title text-lg font-semibold mb-2">Set Your Schedule</h3>
                        <p class="section-subtitle">
                            Define your availability, service areas, and let clients book online 24/7.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="step-circle w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center">
                            <span class="text-2xl font-bold">3</span>
                        </div>
                        <h3 class="section-title text-lg font-semibold mb-2">Get Paid</h3>
                        <p class="section-subtitle">
                            Accept bookings, deliver great service, and receive payments directly to your account.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Pricing Preview -->
        <section class="py-20">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="section-title text-3xl font-bold mb-4">
                    Simple, Transparent Pricing
                </h2>
                <p class="section-subtitle mb-8">
                    Only pay when you get paid. No monthly fees, no hidden costs.
                </p>
                <Card class="pricing-card max-w-md mx-auto">
                    <template #content>
                        <div class="text-center py-6">
                            <p class="text-muted mb-2">Commission per booking</p>
                            <p class="pricing-value text-5xl font-bold mb-2">15%</p>
                            <p class="text-muted text-sm mb-6">Only charged on completed bookings</p>
                            <ul class="text-left space-y-3 mb-8">
                                <li class="flex items-center">
                                    <i class="pi pi-check icon-success mr-2"></i>
                                    Unlimited bookings
                                </li>
                                <li class="flex items-center">
                                    <i class="pi pi-check icon-success mr-2"></i>
                                    Online payment processing
                                </li>
                                <li class="flex items-center">
                                    <i class="pi pi-check icon-success mr-2"></i>
                                    Professional profile page
                                </li>
                                <li class="flex items-center">
                                    <i class="pi pi-check icon-success mr-2"></i>
                                    SMS & email notifications
                                </li>
                                <li class="flex items-center">
                                    <i class="pi pi-check icon-success mr-2"></i>
                                    Business analytics dashboard
                                </li>
                            </ul>
                            <Link href="/register/provider">
                                <Button label="Start Free Today" size="large" class="w-full" />
                            </Link>
                        </div>
                    </template>
                </Card>
            </div>
        </section>

        <!-- CTA Section - Provider Focused -->
        <section class="cta-section py-20">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="cta-title text-3xl font-bold mb-4">
                    Ready to Grow Your Business?
                </h2>
                <p class="cta-subtitle mb-8 text-lg">
                    Join hundreds of service professionals across Jamaica who are building their client base with Zeen.
                </p>
                <div class="flex flex-col sm:flex-row justify-center gap-4">
                    <Link href="/register/provider">
                        <Button size="large" class="cta-btn-primary px-8">
                            Create Your Free Account
                        </Button>
                    </Link>
                    <Link href="/contact">
                        <Button severity="secondary" outlined size="large" class="cta-btn-secondary px-8">
                            Contact Sales
                        </Button>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="footer py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
                    <div class="mb-8">
                        <h3 class="footer-brand text-lg font-semibold mb-4">Zeen</h3>
                        <p class="footer-text text-sm">
                            The booking platform built for service professionals in Jamaica.
                        </p>
                    </div>
                    <div class="mb-8">
                        <h4 class="footer-heading font-medium mb-4">For Providers</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/register/provider" class="footer-link">Get Started</Link></li>
                            <li><Link href="/pricing" class="footer-link">Pricing</Link></li>
                            <li><Link href="/how-it-works" class="footer-link">How It Works</Link></li>
                            <li><Link href="/provider-resources" class="footer-link">Resources</Link></li>
                        </ul>
                    </div>
                    <div class="mb-8">
                        <h4 class="footer-heading font-medium mb-4">For Clients</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/explore" class="footer-link">Find Services</Link></li>
                            <li><Link href="/register" class="footer-link">Create Account</Link></li>
                        </ul>
                    </div>
                    <div class="mb-8">
                        <h4 class="footer-heading font-medium mb-4">Support</h4>
                        <ul class="space-y-2 text-sm">
                            <li><Link href="/help" class="footer-link">Help Center</Link></li>
                            <li><Link href="/contact" class="footer-link">Contact Us</Link></li>
                            <li><Link href="/privacy" class="footer-link">Privacy Policy</Link></li>
                            <li><Link href="/terms" class="footer-link">Terms of Service</Link></li>
                        </ul>
                    </div>
                </div>
                <Divider class="footer-divider my-8" />
                <div class="text-center text-sm footer-copyright">
                    <p>&copy; {{ new Date().getFullYear() }} Zeen. All rights reserved.</p>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Page Background */
.welcome-page {
    background: linear-gradient(to bottom, var(--color-surface-alt), var(--color-surface));
}

/* Header */
.header {
    background-color: color-mix(in srgb, var(--color-surface) 80%, transparent);
    border-color: var(--p-surface-200);
}

.brand-text {
    color: var(--color-primary);
}

.nav-link {
    color: var(--color-text-secondary);
    transition: color 0.2s;
}

.nav-link:hover {
    color: var(--color-primary);
}

/* Hero Section */
.hero-section {
    background: linear-gradient(to bottom, var(--color-surface-alt), var(--color-surface));
}

.hero-title {
    color: var(--color-text-primary);
}

.hero-subtitle {
    color: var(--color-text-secondary);
}

/* Stats */
.stat-value {
    color: var(--color-primary);
}

.stat-label {
    color: var(--color-text-secondary);
}

/* Sections */
.section-alt {
    background-color: var(--color-surface-alt);
}

.section-title {
    color: var(--color-text-primary);
}

.section-subtitle {
    color: var(--color-text-secondary);
}

.card-text {
    color: var(--color-text-secondary);
}

/* Feature Cards */
.feature-card {
    height: 100%;
    transition: transform 0.2s, box-shadow 0.2s;
}

.feature-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.feature-card :deep(.p-card-title) {
    font-size: 1rem;
    padding-bottom: 0;
}

/* Icons */
.icon-primary {
    color: var(--color-primary);
}

.icon-success {
    color: var(--color-success);
}

/* Category Icons */
.category-icon-1 { color: var(--color-category-1, #3b82f6); }
.category-icon-2 { color: var(--color-category-2, #ec4899); }
.category-icon-3 { color: var(--color-category-3, #f59e0b); }
.category-icon-4 { color: var(--color-category-4, #8b5cf6); }
.category-icon-5 { color: var(--color-category-5, #10b981); }
.category-icon-6 { color: var(--color-category-6, #6b7280); }

.category-card {
    transition: transform 0.2s, border-color 0.2s;
}

.category-card:hover {
    transform: translateY(-2px);
    border-color: var(--color-primary);
}

.category-card :deep(.p-card-body) {
    padding: 1rem;
}

.category-card :deep(.p-card-content) {
    padding: 0;
}

/* Step Circle */
.step-circle {
    background-color: var(--color-primary-bg, color-mix(in srgb, var(--color-primary) 15%, transparent));
    color: var(--color-primary);
}

/* Pricing */
.pricing-card {
    border: 2px solid var(--color-primary);
}

.pricing-value {
    color: var(--color-primary);
}

.text-muted {
    color: var(--color-text-muted);
}

/* CTA Section */
.cta-section {
    background-color: var(--color-primary);
}

.cta-title {
    color: var(--color-text-inverse);
}

.cta-subtitle {
    color: color-mix(in srgb, var(--color-text-inverse) 85%, transparent);
}

.cta-btn-primary {
    background-color: var(--color-surface) !important;
    color: var(--color-primary) !important;
    border-color: var(--color-surface) !important;
}

.cta-btn-primary:hover {
    background-color: var(--color-surface-alt) !important;
}

.cta-btn-secondary {
    background-color: transparent !important;
    color: var(--color-text-inverse) !important;
    border-color: var(--color-text-inverse) !important;
}

.cta-btn-secondary:hover {
    background-color: var(--color-primary-dark) !important;
}

/* Footer */
.footer {
    background-color: var(--color-surface-dark);
}

.footer-brand {
    color: var(--color-text-inverse);
}

.footer-heading {
    color: var(--color-text-inverse);
}

.footer-text {
    color: var(--color-text-muted);
}

.footer-link {
    color: var(--color-text-muted);
    transition: color 0.2s;
}

.footer-link:hover {
    color: var(--color-text-inverse);
}

.footer-divider {
    border-color: color-mix(in srgb, var(--color-text-muted) 30%, transparent);
}

.footer-copyright {
    color: var(--color-text-muted);
}
</style>
