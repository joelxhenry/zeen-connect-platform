<template>
    <section class="features">
        <div class="features-container">
            <span class="section-label">Platform Features</span>
            <h2>Everything you need to succeed</h2>

            <div class="bento-grid">
                <!-- Large Feature - Online Booking -->
                <div class="bento-card bento-large">
                    <div class="bento-image">
                        <img
                            src="https://images.unsplash.com/photo-1560066984-138dadb4c035?w=800&h=500&fit=crop"
                            alt="Online Booking"
                        />
                    </div>
                    <div class="bento-content">
                        <div class="bento-icon">
                            <i class="pi pi-calendar"></i>
                        </div>
                        <h3>24/7 Online Booking</h3>
                        <p>Clients book while you sleep. Your calendar stays organized automatically.</p>
                    </div>
                </div>

                <!-- Stacked Features -->
                <div class="bento-stack">
                    <div class="bento-card bento-small">
                        <div class="bento-image">
                            <img
                                src="https://images.unsplash.com/photo-1556742049-0cfed4f6a45d?w=400&h=200&fit=crop"
                                alt="Secure Payments"
                            />
                        </div>
                        <div class="bento-content">
                            <div class="bento-icon">
                                <i class="pi pi-credit-card"></i>
                            </div>
                            <h3>Secure Payments</h3>
                            <p>Accept cards with instant deposits.</p>
                        </div>
                    </div>

                    <div class="bento-card bento-small">
                        <div class="bento-image">
                            <img
                                src="https://images.unsplash.com/photo-1561070791-2526d30994b5?w=400&h=200&fit=crop"
                                alt="Your Brand"
                            />
                        </div>
                        <div class="bento-content">
                            <div class="bento-icon">
                                <i class="pi pi-palette"></i>
                            </div>
                            <h3>Your Own Brand</h3>
                            <p>Custom site with your style.</p>
                        </div>
                    </div>
                </div>

                <!-- Medium Feature - Client Management -->
                <div class="bento-card bento-medium">
                    <div class="bento-image">
                        <img
                            src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?w=600&h=300&fit=crop"
                            alt="Client Management"
                        />
                    </div>
                    <div class="bento-content">
                        <div class="bento-icon">
                            <i class="pi pi-users"></i>
                        </div>
                        <h3>Client Management</h3>
                        <p>Track history, preferences, and build lasting relationships with your clients.</p>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="bento-card bento-bottom">
                    <div class="bento-image">
                        <img
                            src="https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=400&h=250&fit=crop"
                            alt="Smart Reminders"
                        />
                    </div>
                    <div class="bento-content">
                        <div class="bento-icon">
                            <i class="pi pi-bell"></i>
                        </div>
                        <h3>Smart Reminders</h3>
                        <p>Automated SMS & email reminders reduce no-shows.</p>
                    </div>
                </div>

                <div class="bento-card bento-bottom">
                    <div class="bento-image">
                        <img
                            src="https://images.unsplash.com/photo-1551288049-bebda4e38f71?w=400&h=250&fit=crop"
                            alt="Business Insights"
                        />
                    </div>
                    <div class="bento-content">
                        <div class="bento-icon">
                            <i class="pi pi-chart-line"></i>
                        </div>
                        <h3>Business Insights</h3>
                        <p>Track your growth with easy analytics.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.features {
    padding: 5rem 2rem;
    background: #f8faf9;
}

.features-container {
    max-width: 1100px;
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
    font-size: 2rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 2.5rem;
}

.bento-grid {
    display: grid;
    grid-template-columns: 1fr 320px;
    grid-template-rows: auto auto auto;
    gap: 1.25rem;
}

.bento-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 20px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.bento-card:hover {
    border-color: #106B4F30;
    box-shadow: 0 12px 40px rgba(16, 107, 79, 0.1);
    transform: translateY(-4px);
}

.bento-large {
    grid-row: span 2;
}

.bento-stack {
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.bento-small {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.bento-small .bento-image {
    height: 120px;
}

.bento-medium {
    grid-column: span 2;
    display: grid;
    grid-template-columns: 1.2fr 1fr;
}

.bento-medium .bento-image {
    height: 100%;
    min-height: 180px;
}

.bento-medium .bento-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.bento-bottom {
    display: flex;
    flex-direction: column;
}

.bento-bottom .bento-image {
    height: 140px;
}

.bento-image {
    overflow: hidden;
}

.bento-large .bento-image {
    height: 260px;
}

.bento-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.bento-card:hover .bento-image img {
    transform: scale(1.05);
}

.bento-content {
    padding: 1.25rem;
    text-align: left;
}

.bento-icon {
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

.bento-card h3 {
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
    margin-bottom: 0.375rem;
}

.bento-card p {
    font-size: 0.875rem;
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

@media (max-width: 1024px) {
    .bento-grid {
        grid-template-columns: 1fr 1fr;
    }

    .bento-large {
        grid-row: auto;
        grid-column: span 2;
    }

    .bento-stack {
        grid-column: span 2;
        flex-direction: row;
    }

    .bento-small .bento-image {
        height: 100px;
    }

    .bento-medium {
        grid-template-columns: 1fr;
    }

    .bento-medium .bento-image {
        height: 160px;
    }
}

@media (max-width: 640px) {
    .features { padding: 3rem 1rem; }
    .features-container h2 { font-size: 1.5rem; }

    .bento-grid {
        grid-template-columns: 1fr;
    }

    .bento-large,
    .bento-medium {
        grid-column: auto;
    }

    .bento-stack {
        grid-column: auto;
        flex-direction: column;
    }

    .bento-bottom {
        display: flex;
    }
}
</style>
