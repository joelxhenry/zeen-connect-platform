<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface GalleryImage {
    url: string;
    medium?: string;
    large?: string;
    alt?: string;
}

interface GalleryVideo {
    id: number;
    platform: 'youtube' | 'vimeo';
    video_id: string;
    url: string;
    embed_url: string;
    watch_url: string;
    title?: string;
    thumbnail_url?: string;
    human_duration?: string;
}

interface GalleryItem {
    type: 'image' | 'video';
    url: string;
    embedUrl?: string;
    thumbnail?: string;
    alt?: string;
    platform?: 'youtube' | 'vimeo';
}

const props = defineProps<{
    images?: GalleryImage[];
    videos?: GalleryVideo[];
}>();

const lightboxOpen = ref(false);
const currentIndex = ref(0);

// Combine images and videos into gallery items
const galleryItems = computed<GalleryItem[]>(() => {
    const items: GalleryItem[] = [];

    if (props.images) {
        props.images.forEach(img => {
            items.push({
                type: 'image',
                url: img.large || img.medium || img.url,
                thumbnail: img.medium || img.url,
                alt: img.alt
            });
        });
    }

    if (props.videos) {
        props.videos.forEach(vid => {
            items.push({
                type: 'video',
                url: vid.watch_url || vid.url,
                embedUrl: vid.embed_url,
                thumbnail: vid.thumbnail_url,
                alt: vid.title,
                platform: vid.platform
            });
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
    document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
    lightboxOpen.value = false;
    document.body.style.overflow = '';
};

const currentItem = computed(() => galleryItems.value[currentIndex.value]);
const hasPrev = computed(() => currentIndex.value > 0);
const hasNext = computed(() => currentIndex.value < galleryItems.value.length - 1);

const goToPrev = () => {
    if (hasPrev.value) {
        currentIndex.value--;
    }
};

const goToNext = () => {
    if (hasNext.value) {
        currentIndex.value++;
    }
};

// Keyboard navigation
const handleKeydown = (e: KeyboardEvent) => {
    if (!lightboxOpen.value) return;

    switch (e.key) {
        case 'Escape':
            closeLightbox();
            break;
        case 'ArrowLeft':
            goToPrev();
            break;
        case 'ArrowRight':
            goToNext();
            break;
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});
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
                <img :src="item.thumbnail || item.url" :alt="item.alt || 'Gallery image'" class="gallery-image" />
            </template>
            <template v-else>
                <div class="video-thumbnail">
                    <img
                        v-if="item.thumbnail"
                        :src="item.thumbnail"
                        :alt="item.alt || 'Video thumbnail'"
                        class="gallery-image"
                    />
                    <div v-else class="video-placeholder">
                        <i class="pi pi-video"></i>
                    </div>
                    <div class="video-play-icon">
                        <i class="pi pi-play"></i>
                    </div>
                </div>
            </template>
            <div class="gallery-overlay"></div>
        </div>
    </div>

    <!-- Lightbox (Teleport to body like classic template) -->
    <Teleport to="body">
        <Transition name="lightbox">
            <div v-if="lightboxOpen" class="masonry-lightbox" @click.self="closeLightbox">
                <!-- Close button -->
                <button type="button" class="lightbox-close" @click="closeLightbox">
                    <i class="pi pi-times"></i>
                </button>

                <!-- Navigation -->
                <button
                    v-if="hasPrev"
                    type="button"
                    class="lightbox-nav lightbox-prev"
                    @click.stop="goToPrev"
                >
                    <i class="pi pi-chevron-left"></i>
                </button>

                <button
                    v-if="hasNext"
                    type="button"
                    class="lightbox-nav lightbox-next"
                    @click.stop="goToNext"
                >
                    <i class="pi pi-chevron-right"></i>
                </button>

                <!-- Content -->
                <div class="lightbox-content" @click.stop>
                    <!-- Image -->
                    <template v-if="currentItem?.type === 'image'">
                        <img
                            :src="currentItem.url"
                            :alt="currentItem.alt || 'Gallery image'"
                            class="lightbox-image"
                        />
                    </template>

                    <!-- Video (YouTube/Vimeo embed) -->
                    <template v-else-if="currentItem?.type === 'video'">
                        <div class="lightbox-video">
                            <iframe
                                :key="currentItem.embedUrl"
                                :src="currentItem.embedUrl + '?autoplay=1'"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                allowfullscreen
                            ></iframe>
                        </div>
                    </template>
                </div>

                <!-- Counter -->
                <div v-if="galleryItems.length > 1" class="lightbox-counter">
                    {{ currentIndex + 1 }} / {{ galleryItems.length }}
                </div>
            </div>
        </Transition>
    </Teleport>
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

.video-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #27272a 0%, #18181b 100%);
}

.video-placeholder i {
    font-size: 2.5rem;
    color: rgba(255, 255, 255, 0.3);
}

.video-play-icon {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.video-play-icon i {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    border-radius: 50%;
    color: white;
    font-size: 1.25rem;
    padding-left: 3px;
    transition: transform 0.2s, background 0.2s;
}

.gallery-item--large .video-play-icon i {
    width: 72px;
    height: 72px;
    font-size: 1.5rem;
    padding-left: 4px;
}

.gallery-item:hover .video-play-icon i {
    transform: scale(1.1);
    background: rgba(0, 0, 0, 0.75);
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

    .video-play-icon i {
        width: 48px;
        height: 48px;
        font-size: 1rem;
    }
}
</style>

<!-- Non-scoped styles for Teleported lightbox content -->
<style>
.masonry-lightbox {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
}

.masonry-lightbox .lightbox-close {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 10;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
    border-radius: 50%;
    transition: color 0.2s, background 0.2s;
}

.masonry-lightbox .lightbox-close:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
}

.masonry-lightbox .lightbox-close i {
    font-size: 1.25rem;
}

.masonry-lightbox .lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    cursor: pointer;
    border-radius: 50%;
    transition: background 0.2s;
}

.masonry-lightbox .lightbox-nav:hover {
    background: rgba(255, 255, 255, 0.2);
}

.masonry-lightbox .lightbox-nav i {
    font-size: 1.25rem;
}

.masonry-lightbox .lightbox-prev {
    left: 16px;
}

.masonry-lightbox .lightbox-next {
    right: 16px;
}

.masonry-lightbox .lightbox-content {
    max-width: 90vw;
    max-height: 85vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.masonry-lightbox .lightbox-image {
    max-width: 100%;
    max-height: 85vh;
    object-fit: contain;
    border-radius: 4px;
}

.masonry-lightbox .lightbox-video {
    width: 90vw;
    max-width: 1000px;
    aspect-ratio: 16 / 9;
    background: #000;
    border-radius: 4px;
    overflow: hidden;
}

.masonry-lightbox .lightbox-video iframe {
    width: 100%;
    height: 100%;
}

.masonry-lightbox .lightbox-counter {
    position: absolute;
    bottom: 16px;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(255, 255, 255, 0.6);
    font-family: var(--font-body, 'Montserrat', sans-serif);
    font-size: 13px;
    font-weight: 500;
    letter-spacing: 0.05em;
}

/* Lightbox transitions */
.lightbox-enter-active,
.lightbox-leave-active {
    transition: opacity 0.25s ease;
}

.lightbox-enter-from,
.lightbox-leave-to {
    opacity: 0;
}

@media (max-width: 768px) {
    .masonry-lightbox .lightbox-nav {
        width: 40px;
        height: 40px;
    }

    .masonry-lightbox .lightbox-prev {
        left: 8px;
    }

    .masonry-lightbox .lightbox-next {
        right: 8px;
    }
}
</style>
