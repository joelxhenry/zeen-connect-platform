<script setup lang="ts">
import { ref } from 'vue';
import Dialog from 'primevue/dialog';

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

interface Props {
    videos: Video[];
}

defineProps<Props>();

const selectedVideo = ref<Video | null>(null);
const showModal = ref(false);

const openVideo = (video: Video) => {
    selectedVideo.value = video;
    showModal.value = true;
};

const closeVideo = () => {
    showModal.value = false;
    selectedVideo.value = null;
};

const getPlatformIcon = (platform: string) => {
    return platform === 'youtube' ? 'pi pi-youtube' : 'pi pi-video';
};

const getPlatformColor = (platform: string) => {
    return platform === 'youtube' ? '#FF0000' : '#1AB7EA';
};
</script>

<template>
    <div v-if="videos.length > 0" class="provider-videos">
        <div class="videos-grid">
            <button
                v-for="video in videos"
                :key="video.id"
                type="button"
                class="video-card"
                @click="openVideo(video)"
            >
                <div class="video-thumbnail">
                    <img
                        v-if="video.thumbnail_url"
                        :src="video.thumbnail_url"
                        :alt="video.title || 'Video'"
                    />
                    <div v-else class="video-thumbnail-placeholder">
                        <i :class="getPlatformIcon(video.platform)"></i>
                    </div>

                    <!-- Play overlay -->
                    <div class="video-play-overlay">
                        <div class="play-button">
                            <i class="pi pi-play-circle"></i>
                        </div>
                    </div>

                    <!-- Duration badge -->
                    <span v-if="video.human_duration" class="video-duration">
                        {{ video.human_duration }}
                    </span>

                    <!-- Platform badge -->
                    <span class="video-platform" :style="{ background: getPlatformColor(video.platform) }">
                        <i :class="getPlatformIcon(video.platform)"></i>
                    </span>
                </div>
                <div v-if="video.title" class="video-title">
                    {{ video.title }}
                </div>
            </button>
        </div>

        <!-- Video Modal -->
        <Dialog
            v-model:visible="showModal"
            modal
            :closable="true"
            :dismissableMask="true"
            :showHeader="true"
            :header="selectedVideo?.title || 'Video'"
            class="video-dialog"
            :pt="{
                root: { class: 'video-dialog-root' },
                content: { class: 'video-dialog-content' },
            }"
            @hide="closeVideo"
        >
            <div v-if="selectedVideo" class="video-embed-container">
                <iframe
                    :src="selectedVideo.embed_url"
                    frameborder="0"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                    allowfullscreen
                ></iframe>
            </div>
        </Dialog>
    </div>
</template>

<style scoped>
.provider-videos {
    width: 100%;
}

.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
}

.video-card {
    background: white;
    border: none;
    border-radius: 0.75rem;
    overflow: hidden;
    cursor: pointer;
    text-align: left;
    transition: box-shadow 0.2s, transform 0.2s;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.video-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
}

.video-thumbnail {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background: #1a1a1a;
    overflow: hidden;
}

.video-thumbnail img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.video-card:hover .video-thumbnail img {
    transform: scale(1.05);
}

.video-thumbnail-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);
}

.video-thumbnail-placeholder i {
    font-size: 3rem;
    color: rgba(255, 255, 255, 0.5);
}

.video-play-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.3);
    opacity: 0;
    transition: opacity 0.2s;
}

.video-card:hover .video-play-overlay {
    opacity: 1;
}

.play-button {
    width: 64px;
    height: 64px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    transition: transform 0.2s;
}

.video-card:hover .play-button {
    transform: scale(1.1);
}

.play-button i {
    font-size: 2rem;
    color: #106B4F;
    margin-left: 4px;
}

.video-duration {
    position: absolute;
    bottom: 0.5rem;
    right: 0.5rem;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.video-platform {
    position: absolute;
    top: 0.5rem;
    left: 0.5rem;
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.25rem;
    color: white;
}

.video-platform i {
    font-size: 0.875rem;
}

.video-title {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #0D1F1B;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Dialog styles */
:deep(.video-dialog-root) {
    max-width: 900px;
    width: 90vw;
}

:deep(.video-dialog-content) {
    padding: 0 !important;
}

.video-embed-container {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    background: #000;
}

.video-embed-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
</style>
