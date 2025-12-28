<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import InputText from 'primevue/inputtext';
import Button from 'primevue/button';
import { computed } from 'vue';

const page = usePage();
const success = computed(() => (page.props as { flash?: { success?: string } }).flash?.success);

const form = useForm({
    name: '',
    email: '',
    phone: '',
});

const submit = () => {
    form.post('/founding-members', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <section class="hero">
        <div class="hero-container">
            <!-- Left: Content -->
            <div class="hero-content">
                <span class="live-badge">
                    <span class="pulse"></span>
                    Limited spots available
                </span>

                <h1>
                    Lock in <span class="highlight">exclusive perks</span><br />
                    forever.
                </h1>

                <p class="subtitle">
                    Join as a founding member and get lifetime price lock, waived
                    fees, and VIP benefits on Jamaica's premier service booking platform.
                </p>

                <!-- Success State -->
                <div v-if="success" class="success-inline">
                    <i class="pi pi-check-circle"></i>
                    <span>You're in! We'll reach out when it's your turn.</span>
                </div>

                <!-- Form -->
                <form v-else @submit.prevent="submit" class="signup-form">
                    <div class="input-wrapper">
                        <label>Your name</label>
                        <InputText
                            v-model="form.name"
                            placeholder="Jane Doe"
                            :class="{ 'p-invalid': form.errors.name }"
                        />
                        <small v-if="form.errors.name" class="error">{{ form.errors.name }}</small>
                    </div>
                    <div class="input-wrapper">
                        <label>Email address</label>
                        <InputText
                            v-model="form.email"
                            type="email"
                            placeholder="jane@email.com"
                            :class="{ 'p-invalid': form.errors.email }"
                        />
                        <small v-if="form.errors.email" class="error">{{ form.errors.email }}</small>
                    </div>
                    <div class="input-wrapper">
                        <label>Phone number</label>
                        <InputText
                            v-model="form.phone"
                            type="tel"
                            placeholder="876-555-1234"
                            :class="{ 'p-invalid': form.errors.phone }"
                        />
                        <small v-if="form.errors.phone" class="error">{{ form.errors.phone }}</small>
                    </div>
                    <Button type="submit" :loading="form.processing" class="submit-btn">
                        Join the Waitlist
                    </Button>
                </form>

                <p class="form-note">
                    <i class="pi pi-lock"></i>
                    No spam, unsubscribe anytime.
                </p>

                <!-- Decorative dots -->
                <div class="dots-pattern"></div>
            </div>

            <!-- Right: Image -->
            <div class="hero-image">
                <img
                    src="https://images.unsplash.com/photo-1560066984-138dadb4c035?w=600&h=700&fit=crop"
                    alt="Service provider at work"
                    class="main-image"
                />

                <!-- Floating images -->
                <div class="floating-image float-1">
                    <img src="https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?w=100&h=100&fit=crop" alt="" />
                </div>
                <div class="floating-image float-2">
                    <img src="https://images.unsplash.com/photo-1571019614242-c5c5dee9f50b?w=100&h=100&fit=crop" alt="" />
                </div>
                <div class="floating-image float-3">
                    <img src="https://images.unsplash.com/photo-1516975080664-ed2fc6a32937?w=100&h=100&fit=crop" alt="" />
                </div>

                <!-- Curved lines SVG -->
                <svg class="curved-lines" viewBox="0 0 300 400" fill="none">
                    <path d="M50 50 Q150 100 100 200 T200 350" stroke="#9ca3af" stroke-width="1.5" fill="none" opacity="0.4"/>
                    <path d="M100 30 Q200 80 150 180 T250 330" stroke="#9ca3af" stroke-width="1.5" fill="none" opacity="0.3"/>
                </svg>
            </div>
        </div>
    </section>
</template>

<style scoped>
.hero {
    min-height: calc(100vh - 64px);
    display: flex;
    align-items: center;
    padding: 3rem 2rem 4rem;
    background: #e8eeeb;
}

.hero-container {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 4rem;
    align-items: center;
}

/* Content Section */
.hero-content {
    position: relative;
    max-width: 500px;
}

.live-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: white;
    padding: 0.5rem 1rem;
    border-radius: 100px;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #106B4F;
    margin-bottom: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
}

.pulse {
    width: 8px;
    height: 8px;
    background: #1ABC9C;
    border-radius: 50%;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
}

.hero-content h1 {
    font-size: 3.5rem;
    font-weight: 700;
    line-height: 1.1;
    color: #1a2e2a;
    margin-bottom: 1.25rem;
}

.highlight {
    color: #106B4F;
}

.subtitle {
    font-size: 1rem;
    line-height: 1.7;
    color: #5a6b66;
    margin-bottom: 2rem;
    max-width: 380px;
}

/* Signup Form */
.signup-form {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    background: white;
    padding: 1.5rem;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 1rem;
    max-width: 380px;
    z-index: 999;
}

.input-wrapper label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.input-wrapper :deep(.p-inputtext) {
    width: 100%;
    background: #f8faf9;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    color: #1a2e2a;
    transition: all 0.2s;
}

.input-wrapper :deep(.p-inputtext:focus) {
    background: white;
    border-color: #106B4F;
    box-shadow: 0 0 0 3px rgba(16, 107, 79, 0.1);
}

.input-wrapper :deep(.p-inputtext::placeholder) {
    color: #9ca3af;
}

.submit-btn {
    background: #106B4F;
    border: none;
    border-radius: 10px;
    padding: 0.875rem 1.5rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: white;
    transition: all 0.2s;
    margin-top: 0.25rem;
}

.submit-btn:hover {
    background: #0D5A42;
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16, 107, 79, 0.25);
}

.error {
    display: block;
    margin-top: 0.375rem;
    font-size: 0.75rem;
    color: #dc2626;
}

.form-note {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.75rem;
    color: #9ca3af;
    margin-left: 0.5rem;
}

.form-note i {
    font-size: 0.6875rem;
}

/* Success State */
.success-inline {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background: #ecfdf5;
    border: 1px solid #a7f3d0;
    padding: 1rem 1.25rem;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.success-inline i {
    color: #059669;
    font-size: 1.25rem;
}

.success-inline span {
    color: #065f46;
    font-weight: 500;
}

/* Decorative Dots */
.dots-pattern {
    position: absolute;
    bottom: -40px;
    right: 0;
    width: 100px;
    height: 80px;
    background-image: radial-gradient(#106B4F30 1.5px, transparent 1.5px);
    background-size: 10px 10px;
    z-index: 1;
}

/* Hero Image Section */
.hero-image {
    position: relative;
    display: flex;
    justify-content: center;
    align-items: center;
}

.main-image {
    width: 100%;
    max-width: 480px;
    height: auto;
    border-radius: 24px;
    object-fit: cover;
}

/* Floating Images */
.floating-image {
    position: absolute;
    border-radius: 50%;
    overflow: hidden;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    border: 3px solid white;
}

.floating-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.float-1 {
    width: 64px;
    height: 64px;
    top: 10%;
    right: 5%;
}

.float-2 {
    width: 56px;
    height: 56px;
    top: 35%;
    right: -5%;
}

.float-3 {
    width: 72px;
    height: 72px;
    bottom: 15%;
    right: 0;
}

/* Curved Lines */
.curved-lines {
    position: absolute;
    top: 0;
    right: -20px;
    width: 200px;
    height: 100%;
    pointer-events: none;
}

/* Responsive */
@media (max-width: 1024px) {
    .hero-content h1 {
        font-size: 2.75rem;
    }

    .floating-image {
        display: none;
    }

    .curved-lines {
        display: none;
    }
}

@media (max-width: 800px) {
    .hero-container {
        grid-template-columns: 1fr;
        gap: 3rem;
    }

    .hero-content {
        text-align: center;
        max-width: 100%;
        margin: 0 auto;
    }

    .subtitle {
        max-width: 100%;
        margin-left: auto;
        margin-right: auto;
    }

    .hero-content h1 {
        font-size: 2.5rem;
    }

    .signup-form {
        max-width: 100%;
    }

    .dots-pattern {
        display: none;
    }

    .hero-image {
        order: -1;
    }

    .main-image {
        max-width: 400px;
    }

    .form-note {
        justify-content: center;
        margin-left: 0;
    }

    .success-inline {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .hero {
        padding: 5rem 1rem 3rem;
    }

    .hero-content h1 {
        font-size: 2rem;
    }

    .subtitle {
        font-size: 0.9375rem;
    }

    .main-image {
        max-width: 100%;
    }
}
</style>
