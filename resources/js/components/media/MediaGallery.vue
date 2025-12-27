<script setup lang="ts">
import { ref, computed } from 'vue';
import Galleria from 'primevue/galleria';
import Image from 'primevue/image';

interface MediaItem {
    id: number;
    uuid: string;
    url: string;
    thumbnail: string;
    medium: string;
    large: string;
    filename: string;
}

interface VideoEmbed {
    id: number;
    uuid: string;
    platform: string;
    video_id: string;
    embed_url: string;
    thumbnail_url: string | null;
    title: string | null;
}

const props = defineProps<{
    images?: MediaItem[];
    videos?: VideoEmbed[];
    showThumbnails?: boolean;
    autoPlay?: boolean;
    circular?: boolean;
}>();

const activeIndex = ref(0);
const showVideoModal = ref(false);
const activeVideo = ref<VideoEmbed | null>(null);

const allMedia = computed(() => {
    const items: Array<{ type: 'image' | 'video'; data: MediaItem | VideoEmbed }> = [];

    if (props.images) {
        props.images.forEach(img => {
            items.push({ type: 'image', data: img });
        });
    }

    if (props.videos) {
        props.videos.forEach(video => {
            items.push({ type: 'video', data: video });
        });
    }

    return items;
});

const hasMedia = computed(() => allMedia.value.length > 0);

const openVideo = (video: VideoEmbed) => {
    activeVideo.value = video;
    showVideoModal.value = true;
};

const closeVideo = () => {
    activeVideo.value = null;
    showVideoModal.value = false;
};

const responsiveOptions = [
    {
        breakpoint: '991px',
        numVisible: 4
    },
    {
        breakpoint: '767px',
        numVisible: 3
    },
    {
        breakpoint: '575px',
        numVisible: 2
    }
];
</script>

<template>
    <div v-if="hasMedia" class="media-gallery">
        <Galleria
            :value="allMedia"
            :numVisible="5"
            :circular="circular ?? true"
            :autoPlay="autoPlay ?? false"
            :transitionInterval="3000"
            :responsiveOptions="responsiveOptions"
            :showThumbnails="showThumbnails ?? true"
            :showIndicators="!showThumbnails"
            v-model:activeIndex="activeIndex"
        >
            <template #item="slotProps">
                <div class="gallery-main-item">
                    <template v-if="slotProps.item.type === 'image'">
                        <Image
                            :src="(slotProps.item.data as MediaItem).large"
                            :alt="(slotProps.item.data as MediaItem).filename"
                            preview
                            class="main-image"
                        />
                    </template>
                    <template v-else>
                        <div class="video-preview" @click="openVideo(slotProps.item.data as VideoEmbed)">
                            <img
                                v-if="(slotProps.item.data as VideoEmbed).thumbnail_url"
                                :src="(slotProps.item.data as VideoEmbed).thumbnail_url"
                                :alt="(slotProps.item.data as VideoEmbed).title || 'Video'"
                                class="video-thumbnail"
                            />
                            <div v-else class="video-placeholder">
                                <i class="pi pi-video"></i>
                            </div>
                            <div class="play-overlay">
                                <i class="pi pi-play-circle"></i>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <template #thumbnail="slotProps">
                <div class="gallery-thumbnail">
                    <template v-if="slotProps.item.type === 'image'">
                        <img
                            :src="(slotProps.item.data as MediaItem).thumbnail"
                            :alt="(slotProps.item.data as MediaItem).filename"
                        />
                    </template>
                    <template v-else>
                        <div class="thumb-video">
                            <img
                                v-if="(slotProps.item.data as VideoEmbed).thumbnail_url"
                                :src="(slotProps.item.data as VideoEmbed).thumbnail_url"
                                :alt="(slotProps.item.data as VideoEmbed).title || 'Video'"
                            />
                            <div v-else class="thumb-placeholder">
                                <i class="pi pi-video"></i>
                            </div>
                            <i class="pi pi-play thumb-play-icon"></i>
                        </div>
                    </template>
                </div>
            </template>
        </Galleria>

        <!-- Video Modal -->
        <div v-if="showVideoModal && activeVideo" class="video-modal" @click.self="closeVideo">
            <div class="video-modal-content">
                <button class="close-btn" @click="closeVideo">
                    <i class="pi pi-times"></i>
                </button>
                <iframe
                    :src="activeVideo.embed_url"
                    width="100%"
                    height="100%"
                    frameborder="0"
                    allowfullscreen
                ></iframe>
            </div>
        </div>
    </div>
    <div v-else class="no-media">
        <i class="pi pi-images"></i>
        <span>No media available</span>
    </div>
</template>

<style scoped>
.media-gallery {
    width: 100%;
}

.gallery-main-item {
    width: 100%;
    height: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--p-surface-100);
    border-radius: 12px;
    overflow: hidden;
}

.gallery-main-item :deep(.p-image) {
    width: 100%;
    height: 100%;
}

.gallery-main-item :deep(.main-image) {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

.video-preview {
    position: relative;
    width: 100%;
    height: 100%;
    cursor: pointer;
}

.video-thumbnail {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.video-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--p-surface-200);
}

.video-placeholder i {
    font-size: 48px;
    color: var(--p-text-muted-color);
}

.play-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.3);
    transition: background 0.2s;
}

.play-overlay:hover {
    background: rgba(0, 0, 0, 0.5);
}

.play-overlay i {
    font-size: 64px;
    color: white;
}

.gallery-thumbnail {
    width: 80px;
    height: 60px;
    border-radius: 6px;
    overflow: hidden;
}

.gallery-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumb-video {
    position: relative;
    width: 100%;
    height: 100%;
}

.thumb-video img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.thumb-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--p-surface-200);
}

.thumb-placeholder i {
    font-size: 20px;
    color: var(--p-text-muted-color);
}

.thumb-play-icon {
    position: absolute;
    bottom: 4px;
    right: 4px;
    font-size: 12px;
    color: white;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.video-modal {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 40px;
}

.video-modal-content {
    position: relative;
    width: 100%;
    max-width: 900px;
    aspect-ratio: 16 / 9;
    background: black;
    border-radius: 8px;
    overflow: hidden;
}

.close-btn {
    position: absolute;
    top: -40px;
    right: 0;
    background: transparent;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 8px;
}

.close-btn:hover {
    color: var(--p-primary-color);
}

.no-media {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    color: var(--p-text-muted-color);
    background: var(--p-surface-50);
    border-radius: 12px;
    border: 2px dashed var(--p-surface-200);
}

.no-media i {
    font-size: 48px;
    margin-bottom: 12px;
}
</style>
