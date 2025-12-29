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
    columns?: number;
}

const props = withDefaults(defineProps<Props>(), {
    images: () => [],
    videos: () => [],
    columns: 3,
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

const hasMedia = computed(() => allMedia.value.length > 0);

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
    <div v-if="hasMedia" class="rounded-gallery" :style="{ '--columns': columns }">
        <button
            v-for="(item, index) in allMedia"
            :key="`${item.type}-${item.id}`"
            class="gallery-item"
            @click="openLightbox(index)"
        >
            <img
                v-if="item.thumbnail"
                :src="item.thumbnail"
                :alt="item.type === 'image' ? getImageData(item).filename : (getVideoData(item).title || 'Video')"
                class="gallery-image"
            />
            <div v-else class="gallery-placeholder">
                <i :class="item.type === 'video' ? getPlatformIcon(getVideoData(item).platform) : 'pi pi-image'"></i>
            </div>

            <!-- Video indicator -->
            <template v-if="item.type === 'video'">
                <div class="video-overlay">
                    <div class="play-icon">
                        <i class="pi pi-play"></i>
                    </div>
                </div>
                <span v-if="getVideoData(item).human_duration" class="video-duration">
                    {{ getVideoData(item).human_duration }}
                </span>
            </template>
        </button>

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
.rounded-gallery {
    display: grid;
    grid-template-columns: repeat(var(--columns, 3), 1fr);
    gap: 1.5rem;
}

.gallery-item {
    position: relative;
    aspect-ratio: 4 / 3;
    overflow: hidden;
    cursor: pointer;
    border: none;
    padding: 0;
    background: var(--provider-border, #ebe8e4);
    border-radius: 1rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.gallery-item:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
}

.gallery-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.gallery-item:hover .gallery-image {
    transform: scale(1.03);
}

.gallery-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #f5f3f0 0%, #ebe8e4 100%);
}

.gallery-placeholder i {
    font-size: 2rem;
    color: var(--provider-secondary, #8a8a8a);
}

/* Video overlay */
.video-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none;
}

.play-icon {
    width: 56px;
    height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.95);
    border-radius: 50%;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    transition: transform 0.2s;
}

.play-icon i {
    font-size: 1.25rem;
    color: var(--provider-text, #3d3d3d);
    padding-left: 3px;
}

.gallery-item:hover .play-icon {
    transform: scale(1.1);
}

.video-duration {
    position: absolute;
    bottom: 12px;
    right: 12px;
    background: rgba(0, 0, 0, 0.75);
    color: white;
    padding: 4px 8px;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: 4px;
}

/* Lightbox */
.lightbox {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.9);
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
    background: rgba(255, 255, 255, 0.1);
    border: none;
    border-radius: 50%;
    color: rgba(255, 255, 255, 0.8);
    cursor: pointer;
    transition: all 0.2s;
}

.lightbox-close:hover {
    background: rgba(255, 255, 255, 0.2);
    color: white;
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
    border: none;
    border-radius: 50%;
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
    border-radius: 0.5rem;
}

.lightbox-video {
    width: 90vw;
    max-width: 1000px;
    aspect-ratio: 16 / 9;
    background: #000;
    border-radius: 0.5rem;
    overflow: hidden;
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
    font-size: 0.875rem;
    font-weight: 400;
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
@media (max-width: 900px) {
    .rounded-gallery {
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }
}

@media (max-width: 600px) {
    .rounded-gallery {
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .gallery-item {
        border-radius: 0.75rem;
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
</style>
