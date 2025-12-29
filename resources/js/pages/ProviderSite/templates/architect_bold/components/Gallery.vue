<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

interface GalleryImage {
    id: number;
    uuid: string;
    url: string;
    thumbnail: string;
    medium: string;
    large: string;
    filename: string;
}

interface Video {
    id: number;
    uuid: string;
    platform: 'youtube' | 'vimeo';
    video_id: string;
    url: string;
    embed_url: string;
    watch_url: string;
    title?: string;
    thumbnail_url?: string;
    human_duration?: string;
}

interface MediaItem {
    type: 'image' | 'video';
    id: number;
    uuid: string;
    thumbnail: string;
    original: GalleryImage | Video;
}

interface Props {
    images?: GalleryImage[];
    videos?: Video[];
    maxDisplay?: number;
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    images: () => [],
    videos: () => [],
    maxDisplay: 6,
});

// Combine images and videos
const allMedia = computed<MediaItem[]>(() => {
    const items: MediaItem[] = [];

    props.images.forEach((img) => {
        items.push({
            type: 'image',
            id: img.id,
            uuid: img.uuid,
            thumbnail: img.medium || img.thumbnail,
            original: img,
        });
    });

    props.videos.forEach((vid) => {
        items.push({
            type: 'video',
            id: vid.id,
            uuid: vid.uuid,
            thumbnail: vid.thumbnail_url || '',
            original: vid,
        });
    });

    return items;
});

const displayMedia = computed(() => allMedia.value.slice(0, props.maxDisplay));
const remainingCount = computed(() => Math.max(0, allMedia.value.length - props.maxDisplay));
const hasMedia = computed(() => allMedia.value.length > 0);

// Bento layout patterns based on item count
const getItemClass = (index: number, total: number) => {
    if (total === 1) {
        return 'bento-full';
    }
    if (total === 2) {
        return 'bento-half';
    }
    if (total === 3) {
        return index === 0 ? 'bento-large' : 'bento-half';
    }
    if (total === 4) {
        if (index === 0) return 'bento-large';
        return 'bento-third';
    }
    // 5+ items
    if (index === 0) return 'bento-large';
    if (index === 1) return 'bento-medium';
    return 'bento-small';
};

// Lightbox state
const lightboxOpen = ref(false);
const currentIndex = ref(0);

const openLightbox = (index: number) => {
    currentIndex.value = index;
    lightboxOpen.value = true;
    document.body.style.overflow = 'hidden';
};

const closeLightbox = () => {
    lightboxOpen.value = false;
    document.body.style.overflow = '';
};

const currentItem = computed(() => allMedia.value[currentIndex.value]);
const hasPrev = computed(() => currentIndex.value > 0);
const hasNext = computed(() => currentIndex.value < allMedia.value.length - 1);

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

const getVideoData = (item: MediaItem): Video => item.original as Video;
const getImageData = (item: MediaItem): GalleryImage => item.original as GalleryImage;

const getPlatformColor = (platform: string) => {
    return platform === 'youtube' ? '#FF0000' : '#1AB7EA';
};

const getPlatformIcon = (platform: string) => {
    return platform === 'youtube' ? 'pi pi-youtube' : 'pi pi-video';
};
</script>

<template>
    <div v-if="hasMedia" class="architect-gallery">
        <h3 v-if="title" class="gallery-title">{{ title }}</h3>

        <!-- Bento Grid -->
        <div class="bento-grid" :class="`items-${Math.min(displayMedia.length, 6)}`">
            <button
                v-for="(item, index) in displayMedia"
                :key="`${item.type}-${item.id}`"
                type="button"
                class="bento-item"
                :class="getItemClass(index, displayMedia.length)"
                @click="openLightbox(index)"
            >
                <img
                    v-if="item.thumbnail"
                    :src="item.thumbnail"
                    :alt="item.type === 'image' ? getImageData(item).filename : (getVideoData(item).title || 'Video')"
                    class="bento-image"
                />
                <div v-else class="bento-placeholder">
                    <i :class="item.type === 'video' ? getPlatformIcon(getVideoData(item).platform) : 'pi pi-image'"></i>
                </div>

                <!-- Video indicator -->
                <template v-if="item.type === 'video'">
                    <div class="video-play-icon">
                        <i class="pi pi-play"></i>
                    </div>
                    <span v-if="getVideoData(item).human_duration" class="video-duration">
                        {{ getVideoData(item).human_duration }}
                    </span>
                    <span
                        class="video-platform"
                        :style="{ background: getPlatformColor(getVideoData(item).platform) }"
                    >
                        <i :class="getPlatformIcon(getVideoData(item).platform)"></i>
                    </span>
                </template>

                <!-- Remaining count overlay -->
                <div
                    v-if="index === displayMedia.length - 1 && remainingCount > 0"
                    class="bento-more"
                >
                    <span>+{{ remainingCount }}</span>
                </div>
            </button>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <Transition name="lightbox">
                <div v-if="lightboxOpen" class="lightbox" @click.self="closeLightbox">
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
                                :src="getImageData(currentItem).large || getImageData(currentItem).medium"
                                :alt="getImageData(currentItem).filename"
                                class="lightbox-image"
                            />
                        </template>

                        <!-- Video -->
                        <template v-else-if="currentItem?.type === 'video'">
                            <div class="lightbox-video">
                                <iframe
                                    :src="getVideoData(currentItem).embed_url + '?autoplay=1'"
                                    frameborder="0"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                    allowfullscreen
                                ></iframe>
                            </div>
                        </template>
                    </div>

                    <!-- Counter -->
                    <div class="lightbox-counter">
                        {{ currentIndex + 1 }} / {{ allMedia.length }}
                    </div>
                </div>
            </Transition>
        </Teleport>
    </div>
</template>

<style scoped>
.architect-gallery {
    width: 100%;
}

.gallery-title {
    margin: 0 0 1rem 0;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--provider-text, #1a1a1a);
    text-transform: uppercase;
    letter-spacing: 0.1em;
}

/* Bento Grid */
.bento-grid {
    display: grid;
    gap: 2px;
    background: var(--provider-border, #e5e5e5);
    border: 1px solid var(--provider-border, #e5e5e5);
}

/* Grid layouts based on item count */
.bento-grid.items-1 {
    grid-template-columns: 1fr;
    grid-template-rows: 400px;
}

.bento-grid.items-2 {
    grid-template-columns: repeat(2, 1fr);
    grid-template-rows: 300px;
}

.bento-grid.items-3 {
    grid-template-columns: repeat(2, 1fr);
    grid-template-rows: 200px 200px;
}

.bento-grid.items-4 {
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: 200px 200px;
}

.bento-grid.items-5,
.bento-grid.items-6 {
    grid-template-columns: repeat(3, 1fr);
    grid-template-rows: 180px 180px;
}

.bento-item {
    position: relative;
    overflow: hidden;
    cursor: pointer;
    border: none;
    padding: 0;
    background: var(--provider-text, #1a1a1a);
    transition: opacity 0.15s;
}

.bento-item:hover {
    opacity: 0.95;
}

.bento-item:hover .bento-image {
    transform: scale(1.02);
}

/* Bento layout variations */
.bento-full {
    grid-column: 1 / -1;
    grid-row: 1 / -1;
}

.bento-half {
    grid-column: span 1;
    grid-row: span 1;
}

.bento-large {
    grid-column: span 1;
    grid-row: span 2;
}

.bento-medium {
    grid-column: span 1;
    grid-row: span 1;
}

.bento-third {
    grid-column: span 1;
    grid-row: span 1;
}

.bento-small {
    grid-column: span 1;
    grid-row: span 1;
}

.bento-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.bento-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--provider-text, #1a1a1a);
}

.bento-placeholder i {
    font-size: 2rem;
    color: rgba(255, 255, 255, 0.3);
}

/* Video indicators */
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
    background: var(--provider-primary, #1a1a1a);
    color: white;
    font-size: 1.25rem;
    padding-left: 4px;
    transition: transform 0.15s;
}

.bento-item:hover .video-play-icon i {
    transform: scale(1.05);
}

.video-duration {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: var(--provider-primary, #1a1a1a);
    color: white;
    padding: 4px 8px;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.05em;
}

.video-platform {
    position: absolute;
    top: 8px;
    left: 8px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
}

.video-platform i {
    font-size: 12px;
}

/* More overlay */
.bento-more {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.7);
}

.bento-more span {
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
    letter-spacing: 0.05em;
}

/* Lightbox */
.lightbox {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
}

.lightbox-close {
    position: absolute;
    top: 16px;
    right: 16px;
    z-index: 10;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: rgba(255, 255, 255, 0.7);
    cursor: pointer;
    transition: all 0.15s;
}

.lightbox-close:hover {
    color: white;
    border-color: white;
}

.lightbox-close i {
    font-size: 1.25rem;
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 2px solid rgba(255, 255, 255, 0.3);
    color: white;
    cursor: pointer;
    transition: all 0.15s;
}

.lightbox-nav:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: white;
}

.lightbox-nav i {
    font-size: 1.25rem;
}

.lightbox-prev {
    left: 16px;
}

.lightbox-next {
    right: 16px;
}

@media (max-width: 640px) {
    .lightbox-nav {
        width: 40px;
        height: 40px;
    }

    .lightbox-prev {
        left: 8px;
    }

    .lightbox-next {
        right: 8px;
    }
}

.lightbox-content {
    max-width: 90vw;
    max-height: 85vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

.lightbox-image {
    max-width: 100%;
    max-height: 85vh;
    object-fit: contain;
}

.lightbox-video {
    width: 90vw;
    max-width: 1000px;
    aspect-ratio: 16 / 9;
    background: #000;
}

.lightbox-video iframe {
    width: 100%;
    height: 100%;
}

.lightbox-counter {
    position: absolute;
    bottom: 16px;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(255, 255, 255, 0.6);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.1em;
}

/* Lightbox transitions */
.lightbox-enter-active,
.lightbox-leave-active {
    transition: opacity 0.2s ease;
}

.lightbox-enter-from,
.lightbox-leave-to {
    opacity: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .bento-grid.items-3,
    .bento-grid.items-4,
    .bento-grid.items-5,
    .bento-grid.items-6 {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: auto;
    }

    .bento-large {
        grid-column: span 2;
        grid-row: span 1;
    }

    .bento-grid.items-2,
    .bento-grid.items-3,
    .bento-grid.items-4,
    .bento-grid.items-5,
    .bento-grid.items-6 {
        grid-auto-rows: 160px;
    }

    .bento-grid.items-1 {
        grid-template-rows: 250px;
    }
}
</style>
