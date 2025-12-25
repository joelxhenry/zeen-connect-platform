<template>
    <section class="vision">
        <div class="vision-container">
            <span class="section-label">The Vision</span>
            <h2>Built for the Jamaican service economy</h2>
            <p class="vision-text">
                Jamaican service providers deserve tools built for how they work. Zeen was created
                to empower barbers, stylists, trainers, photographers, and every skilled professional
                across the island to grow their business, manage their time, and connect with clients
                — all in one place. This is more than software. It's a movement.
            </p>
        </div>
    </section>
</template>

<style scoped>
.vision {
    padding: 5rem 2rem;
    background: white;
    border-top: 1px solid #e5e7eb;
}

.vision-container {
    max-width: 720px;
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

.vision h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 1.5rem;
}

.vision-text {
    font-size: 1.125rem;
    line-height: 1.8;
    color: #4b5563;
}

@media (max-width: 640px) {
    .vision { padding: 3rem 1rem; }
    .vision h2 { font-size: 1.5rem; }
    .vision-text { font-size: 1rem; }
}
</style>
