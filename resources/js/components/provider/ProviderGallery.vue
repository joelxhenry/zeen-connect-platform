<script setup lang="ts">
import Image from 'primevue/image';

interface GalleryImage {
    id: number;
    uuid: string;
    url: string;
    thumbnail: string;
    medium: string;
    large: string;
    filename: string;
}

interface Props {
    images: GalleryImage[];
    maxDisplay?: number;
}

const props = withDefaults(defineProps<Props>(), {
    maxDisplay: 6,
});

const displayImages = props.images.slice(0, props.maxDisplay);
const remainingCount = Math.max(0, props.images.length - props.maxDisplay);
</script>

<template>
    <div v-if="images.length > 0" class="provider-gallery">
        <div class="gallery-grid">
            <div
                v-for="(image, index) in displayImages"
                :key="image.id"
                class="gallery-item"
                :class="{ 'gallery-item--large': index === 0 }"
            >
                <Image
                    :src="image.medium"
                    :alt="image.filename"
                    :preview="true"
                    :pt="{
                        image: { class: 'gallery-image' },
                        previewMask: { class: 'gallery-preview-mask' }
                    }"
                />
                <!-- Show remaining count on last visible item -->
                <div
                    v-if="index === displayImages.length - 1 && remainingCount > 0"
                    class="gallery-more"
                >
                    +{{ remainingCount }} more
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.provider-gallery {
    width: 100%;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
}

@media (min-width: 768px) {
    .gallery-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 0.75rem;
    }
}

.gallery-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #f3f4f6;
}

.gallery-item--large {
    grid-column: span 2;
    grid-row: span 2;
}

.gallery-item :deep(.p-image) {
    width: 100%;
    height: 100%;
    display: block;
}

.gallery-item :deep(.gallery-image) {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform 0.3s ease;
}

.gallery-item:hover :deep(.gallery-image) {
    transform: scale(1.05);
}

.gallery-item :deep(.gallery-preview-mask) {
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.4);
}

.gallery-more {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    font-size: 1.25rem;
    font-weight: 600;
    pointer-events: none;
}
</style>
