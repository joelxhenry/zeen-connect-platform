<script setup lang="ts">
const emit = defineEmits<{
    (e: 'join'): void;
}>();

// Placeholder avatars for the wall of pioneers
const pioneers = Array.from({ length: 24 }, (_, i) => ({
    id: i + 1,
    image: `https://i.pravatar.cc/80?img=${i + 1}`,
}));
</script>

<template>
    <section class="pioneers">
        <div class="pioneers-container">
            <span class="section-label">The Wall of Pioneers</span>
            <h2>Join the first 100</h2>
            <p class="pioneers-subtitle">These trailblazers are shaping the future of Jamaican services.</p>

            <div class="pioneers-grid">
                <div v-for="pioneer in pioneers" :key="pioneer.id" class="pioneer-avatar">
                    <img :src="pioneer.image" :alt="`Pioneer ${pioneer.id}`" />
                </div>
                <div class="pioneer-placeholder" @click="emit('join')">
                    <i class="pi pi-user-plus"></i>
                    <span>You?</span>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.pioneers {
    padding: 5rem 2rem;
    background: #f8faf9;
    border-top: 1px solid #e5e7eb;
}

.pioneers-container {
    max-width: 800px;
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

.pioneers h2 {
    font-size: 2rem;
    font-weight: 700;
    color: #0D1F1B;
    margin-bottom: 0.5rem;
}

.pioneers-subtitle {
    font-size: 1rem;
    color: #6b7280;
    margin-bottom: 2.5rem;
}

.pioneers-grid {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 0.75rem;
}

.pioneer-avatar {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: all 0.2s;
}

.pioneer-avatar:hover {
    transform: scale(1.1);
    z-index: 1;
}

.pioneer-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.pioneer-placeholder {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    background: linear-gradient(135deg, #106B4F, #1ABC9C);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 0.625rem;
    font-weight: 600;
    gap: 0.125rem;
    cursor: pointer;
    transition: all 0.2s;
}

.pioneer-placeholder i {
    font-size: 1rem;
}

.pioneer-placeholder:hover {
    transform: scale(1.1);
}

@media (max-width: 640px) {
    .pioneers { padding: 3rem 1rem; }
    .pioneers h2 { font-size: 1.5rem; }

    .pioneer-avatar, .pioneer-placeholder {
        width: 48px;
        height: 48px;
    }
}
</style>
