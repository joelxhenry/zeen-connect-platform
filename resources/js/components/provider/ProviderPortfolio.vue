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
}

const props = withDefaults(defineProps<Props>(), {
    images: () => [],
    videos: () => [],
    maxDisplay: 7,
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

// Mosaic layout patterns based on item count
const getItemClass = (index: number, total: number) => {
    // Pattern varies based on total items to create visual interest
    if (total <= 2) {
        return index === 0 ? 'mosaic-wide' : 'mosaic-square';
    }
    if (total <= 4) {
        if (index === 0) return 'mosaic-large';
        return 'mosaic-square';
    }
    // For 5+ items, create an interesting mosaic
    if (index === 0) return 'mosaic-large';
    if (index === 3) return 'mosaic-wide';
    return 'mosaic-square';
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
    <div v-if="hasMedia" class="provider-portfolio">
        <!-- Mosaic Grid -->
        <div class="mosaic-grid">
            <button
                v-for="(item, index) in displayMedia"
                :key="`${item.type}-${item.id}`"
                type="button"
                class="mosaic-item"
                :class="getItemClass(index, displayMedia.length)"
                @click="openLightbox(index)"
            >
                <img
                    v-if="item.thumbnail"
                    :src="item.thumbnail"
                    :alt="item.type === 'image' ? getImageData(item).filename : (getVideoData(item).title || 'Video')"
                    class="mosaic-image"
                />
                <div v-else class="mosaic-placeholder">
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
                    class="mosaic-more"
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
.provider-portfolio {
    width: 100%;
}

/* Mosaic Grid */
.mosaic-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    grid-auto-rows: 140px;
    gap: 4px;
}

@media (min-width: 640px) {
    .mosaic-grid {
        grid-auto-rows: 180px;
        gap: 6px;
    }
}

@media (min-width: 1024px) {
    .mosaic-grid {
        grid-auto-rows: 200px;
    }
}

.mosaic-item {
    position: relative;
    overflow: hidden;
    border-radius: 4px;
    cursor: pointer;
    border: none;
    padding: 0;
    background: #18181b;
    transition: opacity 0.2s;
}

.mosaic-item:hover {
    opacity: 0.9;
}

.mosaic-item:hover .mosaic-image {
    transform: scale(1.03);
}

/* Mosaic layout variations */
.mosaic-large {
    grid-column: span 2;
    grid-row: span 2;
}

.mosaic-wide {
    grid-column: span 2;
    grid-row: span 1;
}

.mosaic-tall {
    grid-column: span 1;
    grid-row: span 2;
}

.mosaic-square {
    grid-column: span 1;
    grid-row: span 1;
}

.mosaic-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.mosaic-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #27272a 0%, #18181b 100%);
}

.mosaic-placeholder i {
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
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    border-radius: 50%;
    color: white;
    font-size: 1rem;
    padding-left: 3px;
    transition: transform 0.2s, background 0.2s;
}

.mosaic-large .video-play-icon i {
    width: 64px;
    height: 64px;
    font-size: 1.25rem;
    padding-left: 4px;
}

.mosaic-item:hover .video-play-icon i {
    transform: scale(1.1);
    background: rgba(0, 0, 0, 0.75);
}

.video-duration {
    position: absolute;
    bottom: 8px;
    right: 8px;
    background: rgba(0, 0, 0, 0.75);
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 500;
    letter-spacing: 0.02em;
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
    border-radius: 4px;
    color: white;
}

.video-platform i {
    font-size: 12px;
}

/* More overlay */
.mosaic-more {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.65);
    backdrop-filter: blur(2px);
}

.mosaic-more span {
    color: white;
    font-size: 1.5rem;
    font-weight: 600;
    letter-spacing: -0.02em;
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

.lightbox-close:hover {
    color: white;
    background: rgba(255, 255, 255, 0.1);
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
    background: rgba(255, 255, 255, 0.1);
    border: none;
    color: white;
    cursor: pointer;
    border-radius: 50%;
    transition: background 0.2s;
}

.lightbox-nav:hover {
    background: rgba(255, 255, 255, 0.2);
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
    border-radius: 4px;
}

.lightbox-video {
    width: 90vw;
    max-width: 1000px;
    aspect-ratio: 16 / 9;
    background: #000;
    border-radius: 4px;
    overflow: hidden;
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
</style>
