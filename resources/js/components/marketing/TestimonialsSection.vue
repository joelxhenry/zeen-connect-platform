<script setup lang="ts">
const testimonials = [
    {
        name: 'Marcia Thompson',
        role: 'Hair Stylist',
        location: 'Kingston',
        image: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150&h=150&fit=crop&crop=face',
        quote: 'Zeen Connect transformed my hair business. I went from chasing clients to having a fully booked calendar every week!',
        rating: 5,
    },
    {
        name: 'Andre Williams',
        role: 'Personal Trainer',
        location: 'Montego Bay',
        image: 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face',
        quote: 'The booking system is so easy. My clients love being able to book sessions online, and I love not missing any appointments.',
        rating: 5,
    },
    {
        name: 'Keisha Brown',
        role: 'Photographer',
        location: 'Ocho Rios',
        image: 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=150&h=150&fit=crop&crop=face',
        quote: 'Since joining Zeen, my photography business has grown by 60%. The platform brings me clients I would never have reached otherwise.',
        rating: 5,
    },
];
</script>

<template>
    <section class="testimonials-section">
        <div class="section-header">
            <span class="section-badge">Success Stories</span>
            <h2>Loved by Providers Across Jamaica</h2>
            <p>See what service professionals are saying about Zeen Connect.</p>
        </div>
        <div class="testimonials-grid">
            <div v-for="testimonial in testimonials" :key="testimonial.name" class="testimonial-card">
                <div class="testimonial-quote">
                    <i class="pi pi-quote-left"></i>
                    <p>{{ testimonial.quote }}</p>
                </div>
                <div class="testimonial-rating">
                    <i v-for="n in testimonial.rating" :key="n" class="pi pi-star-fill"></i>
                </div>
                <div class="testimonial-author">
                    <img :src="testimonial.image" :alt="testimonial.name" />
                    <div class="author-info">
                        <strong>{{ testimonial.name }}</strong>
                        <span>{{ testimonial.role }} &bull; {{ testimonial.location }}</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.testimonials-section {
    padding: 5rem 2rem;
    background: #f8faf9;
}

.section-header {
    text-align: center;
    max-width: 600px;
    margin: 0 auto 3rem;
}

.section-badge {
    display: inline-block;
    background: #106B4F15;
    color: #106B4F;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 1rem;
}

.section-header h2 {
    font-size: 2.25rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 0.75rem;
}

.section-header p {
    font-size: 1.125rem;
    color: #6b7280;
}

.testimonials-grid {
    max-width: 1200px;
    margin: 0 auto;
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
}

.testimonial-card {
    background: white;
    padding: 2rem;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
}

.testimonial-quote {
    margin-bottom: 1rem;
}

.testimonial-quote i {
    color: #106B4F;
    font-size: 1.5rem;
    margin-bottom: 1rem;
    display: block;
}

.testimonial-quote p {
    color: #4b5563;
    line-height: 1.7;
    font-style: italic;
}

.testimonial-rating {
    margin-bottom: 1.5rem;
}

.testimonial-rating i {
    color: #F59E0B;
    font-size: 0.875rem;
    margin-right: 0.125rem;
}

.testimonial-author {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e5e5e5;
}

.testimonial-author img {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    object-fit: cover;
}

.author-info {
    display: flex;
    flex-direction: column;
}

.author-info strong {
    color: #0D1F1B;
    font-weight: 600;
}

.author-info span {
    color: #6b7280;
    font-size: 0.875rem;
}

/* Mobile Responsive */
@media (max-width: 1024px) {
    .testimonials-grid {
        grid-template-columns: 1fr;
        max-width: 500px;
    }
}

@media (max-width: 768px) {
    .testimonials-section {
        padding: 3rem 1rem;
    }

    .section-header h2 {
        font-size: 1.75rem;
    }
}
</style>
