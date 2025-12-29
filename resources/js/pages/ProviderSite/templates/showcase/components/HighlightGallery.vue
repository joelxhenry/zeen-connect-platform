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
    maxDisplay: 5,
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
const thumbnailMedia = computed(() => displayMedia.value.slice(1, 5));
const remainingCount = computed(() => Math.max(0, allMedia.value.length - props.maxDisplay));
const hasMedia = computed(() => allMedia.value.length > 0);
const featuredItem = computed(() => displayMedia.value[0]);

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

const getPlatformIcon = (platform: string) => {
    return platform === 'youtube' ? 'pi pi-youtube' : 'pi pi-video';
};
</script>

<template>
    <div v-if="hasMedia" class="highlight-gallery">
        <!-- Featured Large Image (Left) -->
        <button
            v-if="featuredItem"
            class="highlight-gallery__featured"
            @click="openLightbox(0)"
        >
            <img
                v-if="featuredItem.thumbnail"
                :src="featuredItem.thumbnail"
                :alt="featuredItem.type === 'image' ? getImageData(featuredItem).filename : (getVideoData(featuredItem).title || 'Video')"
                class="featured-image"
            />
            <div v-else class="featured-placeholder">
                <i class="pi pi-image"></i>
            </div>

            <!-- Video indicator -->
            <template v-if="featuredItem.type === 'video'">
                <div class="video-play-icon video-play-icon--large">
                    <i class="pi pi-play"></i>
                </div>
                <span v-if="getVideoData(featuredItem).human_duration" class="video-duration">
                    {{ getVideoData(featuredItem).human_duration }}
                </span>
            </template>
        </button>

        <!-- Thumbnails Grid (Right 2x2) -->
        <div class="highlight-gallery__thumbnails">
            <button
                v-for="(item, index) in thumbnailMedia"
                :key="`${item.type}-${item.id}`"
                class="highlight-gallery__thumb"
                @click="openLightbox(index + 1)"
            >
                <img
                    v-if="item.thumbnail"
                    :src="item.thumbnail"
                    :alt="item.type === 'image' ? getImageData(item).filename : (getVideoData(item).title || 'Video')"
                    class="thumb-image"
                />
                <div v-else class="thumb-placeholder">
                    <i :class="item.type === 'video' ? getPlatformIcon(getVideoData(item).platform) : 'pi pi-image'"></i>
                </div>

                <!-- Video indicator -->
                <template v-if="item.type === 'video'">
                    <div class="video-play-icon">
                        <i class="pi pi-play"></i>
                    </div>
                </template>

                <!-- "+X more" overlay on last thumbnail -->
                <div
                    v-if="index === thumbnailMedia.length - 1 && remainingCount > 0"
                    class="highlight-gallery__more"
                >
                    <span class="more-count">+{{ remainingCount }}</span>
                    <span class="more-label">more</span>
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
.highlight-gallery {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 0.5rem;
    height: 500px;
}

/* Featured large image - spans full left column */
.highlight-gallery__featured {
    position: relative;
    overflow: hidden;
    cursor: pointer;
    border: none;
    padding: 0;
    background: var(--provider-text, #1a1a1a);
    border-radius: 0;
}

.highlight-gallery__featured:hover .featured-image {
    transform: scale(1.02);
}

.featured-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.featured-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #27272a 0%, #18181b 100%);
}

.featured-placeholder i {
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.3);
}

/* Thumbnail grid - right column */
.highlight-gallery__thumbnails {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    grid-template-rows: repeat(2, 1fr);
    gap: 0.5rem;
}

.highlight-gallery__thumb {
    position: relative;
    overflow: hidden;
    cursor: pointer;
    border: none;
    padding: 0;
    background: var(--provider-text, #1a1a1a);
    border-radius: 0;
}

.highlight-gallery__thumb:hover .thumb-image {
    transform: scale(1.05);
}

.thumb-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.thumb-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #27272a 0%, #18181b 100%);
}

.thumb-placeholder i {
    font-size: 1.5rem;
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
    background: rgba(0, 0, 0, 0.7);
    color: white;
    font-size: 1rem;
    padding-left: 3px;
    border-radius: 0;
    transition: transform 0.2s;
}

.video-play-icon--large i {
    width: 72px;
    height: 72px;
    font-size: 1.5rem;
    padding-left: 4px;
}

.highlight-gallery__featured:hover .video-play-icon i,
.highlight-gallery__thumb:hover .video-play-icon i {
    transform: scale(1.1);
}

.video-duration {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 4px 8px;
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.05em;
}

/* "+X more" overlay */
.highlight-gallery__more {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.75);
    color: white;
}

.more-count {
    font-family: var(--font-heading, 'Oswald', sans-serif);
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.more-label {
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin-top: 0.25rem;
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
    top: 20px;
    right: 20px;
    z-index: 10;
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: all 0.2s;
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
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    color: white;
    cursor: pointer;
    transition: all 0.2s;
}

.lightbox-nav:hover {
    background: rgba(255, 255, 255, 0.2);
}

.lightbox-nav i {
    font-size: 1.5rem;
}

.lightbox-prev {
    left: 20px;
}

.lightbox-next {
    right: 20px;
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
    bottom: 20px;
    left: 50%;
    transform: translateX(-50%);
    color: rgba(255, 255, 255, 0.6);
    font-family: var(--font-mono, 'Space Mono', monospace);
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.1em;
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

/* Responsive */
@media (max-width: 768px) {
    .highlight-gallery {
        grid-template-columns: 1fr;
        grid-template-rows: 280px auto;
        height: auto;
    }

    .highlight-gallery__thumbnails {
        grid-template-columns: repeat(4, 1fr);
        grid-template-rows: 80px;
    }

    .video-play-icon--large i {
        width: 56px;
        height: 56px;
        font-size: 1.25rem;
    }

    .lightbox-nav {
        width: 44px;
        height: 44px;
    }

    .lightbox-prev {
        left: 10px;
    }

    .lightbox-next {
        right: 10px;
    }
}

@media (max-width: 480px) {
    .highlight-gallery__thumbnails {
        grid-template-columns: repeat(2, 1fr);
        grid-template-rows: repeat(2, 80px);
    }
}
</style>
