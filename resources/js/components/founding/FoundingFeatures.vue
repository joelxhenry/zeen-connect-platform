<template>
    <section class="features">
        <div class="features-container">
            <span class="section-label">Platform Features</span>
            <h2>Everything you need to succeed</h2>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="pi pi-calendar"></i>
                    </div>
                    <h3>24/7 Online Booking</h3>
                    <p>Clients book while you sleep. Your calendar stays organized automatically.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="pi pi-credit-card"></i>
                    </div>
                    <h3>Secure Payments</h3>
                    <p>Accept cards with instant deposits to your account.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="pi pi-palette"></i>
                    </div>
                    <h3>Your Own Brand</h3>
                    <p>Custom booking page with your colors and style.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="pi pi-users"></i>
                    </div>
                    <h3>Client Management</h3>
                    <p>Track history and build lasting relationships.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="pi pi-bell"></i>
                    </div>
                    <h3>Smart Reminders</h3>
                    <p>Automated SMS & email reminders reduce no-shows.</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="pi pi-users"></i>
                    </div>
                    <h3>Team Management</h3>
                    <p>Add team members with their own calendars and bookings.</p>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.features {
    padding: 4rem 2rem;
    background: #f8faf9;
}

.features-container {
    max-width: 900px;
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

.features-container h2 {
    font-size: 1.75rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 2rem;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}

.feature-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 1.5rem;
    text-align: left;
    transition: all 0.2s ease;
}

.feature-card:hover {
    border-color: #106B4F30;
    box-shadow: 0 8px 24px rgba(16, 107, 79, 0.08);
    transform: translateY(-2px);
}

.feature-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #106B4F15, #1ABC9C15);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #106B4F;
    font-size: 1rem;
    margin-bottom: 0.75rem;
}

.feature-card h3 {
    font-size: 0.9375rem;
    font-weight: 600;
    color: #0D1F1B;
    margin-bottom: 0.375rem;
}

.feature-card p {
    font-size: 0.8125rem;
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

@media (max-width: 768px) {
    .features {
        padding: 3rem 1rem;
    }

    .features-container h2 {
        font-size: 1.5rem;
    }

    .features-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 480px) {
    .features-grid {
        grid-template-columns: 1fr;
    }
}
</style>
