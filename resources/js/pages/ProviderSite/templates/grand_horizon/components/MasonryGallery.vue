<script setup lang="ts">
import { ref, computed } from 'vue';
import Dialog from 'primevue/dialog';

interface GalleryImage {
    url: string;
    alt?: string;
}

interface GalleryVideo {
    url: string;
    thumbnail?: string;
}

const props = defineProps<{
    images?: GalleryImage[];
    videos?: GalleryVideo[];
}>();

const lightboxOpen = ref(false);
const currentIndex = ref(0);

// Combine images and videos into gallery items
const galleryItems = computed(() => {
    const items: Array<{ type: 'image' | 'video'; url: string; thumbnail?: string; alt?: string }> = [];

    if (props.images) {
        props.images.forEach(img => {
            items.push({ type: 'image', url: img.url, alt: img.alt });
        });
    }

    if (props.videos) {
        props.videos.forEach(vid => {
            items.push({ type: 'video', url: vid.url, thumbnail: vid.thumbnail });
        });
    }

    return items;
});

// Assign span classes for masonry effect
const getItemClass = (index: number) => {
    // First item is always large (2x2)
    if (index === 0) return 'gallery-item--large';

    // Create a varied pattern
    const pattern = index % 6;
    switch (pattern) {
        case 1:
            return 'gallery-item--tall';
        case 3:
            return 'gallery-item--wide';
        default:
            return '';
    }
};

const openLightbox = (index: number) => {
    currentIndex.value = index;
    lightboxOpen.value = true;
};

const closeLightbox = () => {
    lightboxOpen.value = false;
};

const prevImage = () => {
    currentIndex.value = currentIndex.value > 0 ? currentIndex.value - 1 : galleryItems.value.length - 1;
};

const nextImage = () => {
    currentIndex.value = currentIndex.value < galleryItems.value.length - 1 ? currentIndex.value + 1 : 0;
};

const currentItem = computed(() => galleryItems.value[currentIndex.value]);

// Keyboard navigation
const handleKeydown = (e: KeyboardEvent) => {
    if (!lightboxOpen.value) return;
    if (e.key === 'ArrowLeft') prevImage();
    if (e.key === 'ArrowRight') nextImage();
    if (e.key === 'Escape') closeLightbox();
};

// Listen for keyboard events
if (typeof window !== 'undefined') {
    window.addEventListener('keydown', handleKeydown);
}
</script>

<template>
    <div v-if="galleryItems.length > 0" class="masonry-gallery">
        <div
            v-for="(item, index) in galleryItems"
            :key="index"
            class="gallery-item"
            :class="getItemClass(index)"
            @click="openLightbox(index)"
        >
            <template v-if="item.type === 'image'">
                <img :src="item.url" :alt="item.alt || 'Gallery image'" class="gallery-image" />
            </template>
            <template v-else>
                <div class="video-thumbnail">
                    <img
                        :src="item.thumbnail || item.url"
                        :alt="'Video thumbnail'"
                        class="gallery-image"
                    />
                    <div class="video-play-icon">
                        <i class="pi pi-play-circle"></i>
                    </div>
                </div>
            </template>
            <div class="gallery-overlay"></div>
        </div>
    </div>

    <!-- Lightbox -->
    <Dialog
        v-model:visible="lightboxOpen"
        modal
        :dismissableMask="true"
        :showHeader="false"
        :style="{ width: '95vw', maxWidth: '1200px' }"
        class="masonry-lightbox"
        @hide="closeLightbox"
    >
        <div class="lightbox-content" @click.stop>
            <button class="lightbox-close" @click="closeLightbox">
                <i class="pi pi-times"></i>
            </button>

            <button v-if="galleryItems.length > 1" class="lightbox-nav lightbox-nav--prev" @click="prevImage">
                <i class="pi pi-chevron-left"></i>
            </button>

            <div class="lightbox-media">
                <template v-if="currentItem?.type === 'image'">
                    <img :src="currentItem.url" :alt="currentItem.alt || 'Gallery image'" />
                </template>
                <template v-else-if="currentItem?.type === 'video'">
                    <video :src="currentItem.url" controls autoplay class="lightbox-video"></video>
                </template>
            </div>

            <button v-if="galleryItems.length > 1" class="lightbox-nav lightbox-nav--next" @click="nextImage">
                <i class="pi pi-chevron-right"></i>
            </button>

            <div v-if="galleryItems.length > 1" class="lightbox-counter">
                {{ currentIndex + 1 }} / {{ galleryItems.length }}
            </div>
        </div>
    </Dialog>
</template>

<style scoped>
.masonry-gallery {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-auto-rows: 200px;
    gap: 0.75rem;
}

.gallery-item {
    position: relative;
    overflow: hidden;
    cursor: pointer;
}

.gallery-item--large {
    grid-column: span 2;
    grid-row: span 2;
}

.gallery-item--tall {
    grid-row: span 2;
}

.gallery-item--wide {
    grid-column: span 2;
}

.gallery-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.gallery-item:hover .gallery-image {
    transform: scale(1.05);
}

.gallery-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to bottom,
        transparent 0%,
        transparent 60%,
        rgba(0, 0, 0, 0.3) 100%
    );
    opacity: 0;
    transition: opacity 0.3s ease;
}

.gallery-item:hover .gallery-overlay {
    opacity: 1;
}

.video-thumbnail {
    position: relative;
    width: 100%;
    height: 100%;
}

.video-play-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.9);
    transition: transform 0.3s ease, color 0.3s ease;
}

.gallery-item:hover .video-play-icon {
    transform: translate(-50%, -50%) scale(1.1);
    color: #ffffff;
}

/* Lightbox Styles */
:deep(.masonry-lightbox .p-dialog-content) {
    background: #000;
    padding: 0;
    border-radius: 0;
}

.lightbox-content {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 70vh;
    padding: 2rem;
}

.lightbox-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: transparent;
    border: none;
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0.5rem;
    z-index: 10;
    transition: opacity 0.2s;
}

.lightbox-close:hover {
    opacity: 0.7;
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: #fff;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 1rem;
    transition: background 0.2s;
    z-index: 10;
}

.lightbox-nav:hover {
    background: rgba(255, 255, 255, 0.2);
}

.lightbox-nav--prev {
    left: 1rem;
}

.lightbox-nav--next {
    right: 1rem;
}

.lightbox-media {
    max-width: 100%;
    max-height: 80vh;
}

.lightbox-media img {
    max-width: 100%;
    max-height: 80vh;
    object-fit: contain;
}

.lightbox-video {
    max-width: 100%;
    max-height: 80vh;
}

.lightbox-counter {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(255, 255, 255, 0.7);
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 0.875rem;
    letter-spacing: 0.1em;
}

/* Responsive */
@media (max-width: 1024px) {
    .masonry-gallery {
        grid-template-columns: repeat(3, 1fr);
        grid-auto-rows: 180px;
    }

    .gallery-item--large {
        grid-column: span 2;
        grid-row: span 2;
    }
}

@media (max-width: 768px) {
    .masonry-gallery {
        grid-template-columns: repeat(2, 1fr);
        grid-auto-rows: 150px;
        gap: 0.5rem;
    }

    .gallery-item--large {
        grid-column: span 2;
        grid-row: span 1;
    }

    .gallery-item--tall {
        grid-row: span 1;
    }

    .gallery-item--wide {
        grid-column: span 2;
    }

    .video-play-icon {
        font-size: 2rem;
    }

    .lightbox-nav {
        padding: 0.75rem;
        font-size: 1.25rem;
    }
}
</style>
