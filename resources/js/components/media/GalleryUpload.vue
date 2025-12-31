<script setup lang="ts">
import { ref, computed } from 'vue';
import ProgressSpinner from 'primevue/progressspinner';
import InputText from 'primevue/inputtext';
import { useApi } from '@/composables/useApi';
import { ApiError } from '@/types/api';

interface MediaItem {
    id: number;
    uuid: string;
    url: string;
    thumbnail: string;
    medium: string;
    large: string;
    filename: string;
    order: number;
}

interface VideoEmbed {
    id: number;
    uuid: string;
    platform: string;
    video_id: string;
    url: string;
    embed_url: string;
    title: string | null;
    thumbnail_url: string | null;
    order: number;
}

interface MediaListResponse {
    media: MediaItem[];
}

interface VideoResponse {
    success: boolean;
    video: VideoEmbed;
    error?: string;
}

const props = defineProps<{
    modelValue: MediaItem[];
    videos?: VideoEmbed[];
    uploadUrl: string;
    videoAddUrl?: string;
    deleteUrl?: string;
    collection?: string;
    maxFiles?: number;
    maxVideos?: number;
    maxFileSize?: number;
    acceptedTypes?: string;
    showVideos?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: MediaItem[]): void;
    (e: 'update:videos', value: VideoEmbed[]): void;
    (e: 'uploaded', media: MediaItem): void;
    (e: 'deleted', id: number): void;
    (e: 'reordered', ids: number[]): void;
    (e: 'videoAdded', video: VideoEmbed): void;
    (e: 'videoDeleted', id: number): void;
    (e: 'error', message: string): void;
}>();

const api = useApi({ showErrorToast: false });
const uploading = ref(false);
const deletingId = ref<number | null>(null);
const error = ref<string | null>(null);
const draggedItem = ref<MediaItem | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const isDragOver = ref(false);

// Video form state
const showVideoForm = ref(false);
const videoUrl = ref('');
const addingVideo = ref(false);
const videoError = ref<string | null>(null);

const maxSize = computed(() => props.maxFileSize || 10485760); // 10MB default
const accept = computed(() => props.acceptedTypes || 'image/jpeg,image/png,image/gif,image/webp');
const max = computed(() => props.maxFiles || 6);
const maxVids = computed(() => props.maxVideos || 3);

const canUpload = computed(() => props.modelValue.length < max.value);
const remainingSlots = computed(() => max.value - props.modelValue.length);
const canAddVideo = computed(() => props.showVideos && (props.videos?.length || 0) < maxVids.value);

const triggerFileSelect = () => {
    if (!uploading.value) {
        fileInput.value?.click();
    }
};

const onFileSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const files = target.files;
    if (files && files.length > 0) {
        await uploadFiles(Array.from(files));
    }
    target.value = '';
};

const onDragEnter = (e: DragEvent) => {
    e.preventDefault();
    isDragOver.value = true;
};

const onDragLeave = (e: DragEvent) => {
    e.preventDefault();
    isDragOver.value = false;
};

const onDragOverSlot = (e: DragEvent) => {
    e.preventDefault();
};

const onDropFiles = async (e: DragEvent) => {
    e.preventDefault();
    isDragOver.value = false;

    const files = e.dataTransfer?.files;
    if (files && files.length > 0) {
        const imageFiles = Array.from(files).filter(f => f.type.startsWith('image/'));
        if (imageFiles.length > 0) {
            await uploadFiles(imageFiles);
        }
    }
};

const uploadFiles = async (files: File[]) => {
    const toUpload = files.slice(0, remainingSlots.value);
    if (toUpload.length === 0) return;

    // Check file sizes
    const oversizedFiles = toUpload.filter(f => f.size > maxSize.value);
    if (oversizedFiles.length > 0) {
        const maxMB = Math.round(maxSize.value / 1048576);
        error.value = `Some files exceed the ${maxMB}MB size limit`;
        emit('error', error.value);
        return;
    }

    uploading.value = true;
    error.value = null;

    const formData = new FormData();
    toUpload.forEach((file: File) => {
        formData.append('files[]', file);
    });
    formData.append('collection', props.collection || 'gallery');

    try {
        const result = await api.upload<MediaListResponse>(props.uploadUrl, formData);
        const newMedia = [...props.modelValue, ...result.media];
        emit('update:modelValue', newMedia);
        result.media.forEach((m: MediaItem) => emit('uploaded', m));
    } catch (e) {
        const message = e instanceof ApiError ? e.message : 'Upload failed';
        error.value = message;
        emit('error', message);
    } finally {
        uploading.value = false;
    }
};

const onRemove = async (media: MediaItem) => {
    if (deletingId.value) return;

    deletingId.value = media.id;
    const deleteEndpoint = props.deleteUrl || `/media/${media.id}`;

    try {
        await api.delete(deleteEndpoint);
        const newMedia = props.modelValue.filter(m => m.id !== media.id);
        emit('update:modelValue', newMedia);
        emit('deleted', media.id);
    } catch (e) {
        const message = e instanceof ApiError ? e.message : 'Delete failed';
        error.value = message;
        emit('error', message);
    } finally {
        deletingId.value = null;
    }
};

// Drag and drop reordering
const onDragStart = (media: MediaItem) => {
    draggedItem.value = media;
};

const onDragOver = (event: DragEvent, targetMedia: MediaItem) => {
    event.preventDefault();
    if (!draggedItem.value || draggedItem.value.id === targetMedia.id) return;

    const items = [...props.modelValue];
    const draggedIndex = items.findIndex(m => m.id === draggedItem.value!.id);
    const targetIndex = items.findIndex(m => m.id === targetMedia.id);

    items.splice(draggedIndex, 1);
    items.splice(targetIndex, 0, draggedItem.value);

    emit('update:modelValue', items);
};

const onDragEnd = () => {
    if (draggedItem.value) {
        const orderedIds = props.modelValue.map(m => m.id);
        emit('reordered', orderedIds);
    }
    draggedItem.value = null;
};

// Video functions
const addVideo = async () => {
    if (!videoUrl.value.trim() || !props.videoAddUrl) return;

    addingVideo.value = true;
    videoError.value = null;

    try {
        const response = await fetch(props.videoAddUrl, {
            method: 'POST',
            body: JSON.stringify({ url: videoUrl.value.trim() }),
            headers: {
                'X-XSRF-TOKEN': document.cookie
                    .split('; ')
                    .find(row => row.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1]?.replace('%3D', '=') || '',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });

        const data: VideoResponse = await response.json();

        if (data.success && data.video) {
            const newVideos = [...(props.videos || []), data.video];
            emit('update:videos', newVideos);
            emit('videoAdded', data.video);
            videoUrl.value = '';
            showVideoForm.value = false;
        } else {
            videoError.value = data.error || 'Failed to add video';
        }
    } catch (e: any) {
        videoError.value = e.message || 'Failed to add video';
    } finally {
        addingVideo.value = false;
    }
};

const removeVideo = async (video: VideoEmbed) => {
    try {
        const response = await fetch(`/videos/${video.id}`, {
            method: 'DELETE',
            headers: {
                'X-XSRF-TOKEN': document.cookie
                    .split('; ')
                    .find(row => row.startsWith('XSRF-TOKEN='))
                    ?.split('=')[1]?.replace('%3D', '=') || '',
                'Accept': 'application/json',
            },
            credentials: 'include',
        });

        const data = await response.json();

        if (data.success) {
            const newVideos = (props.videos || []).filter(v => v.id !== video.id);
            emit('update:videos', newVideos);
            emit('videoDeleted', video.id);
        }
    } catch (e: any) {
        error.value = e.message || 'Delete failed';
        emit('error', error.value!);
    }
};

const getPlatformIcon = (platform: string) => {
    return platform === 'youtube' ? 'pi pi-youtube' : 'pi pi-video';
};
</script>

<template>
    <div class="gallery-upload">
        <input
            ref="fileInput"
            type="file"
            :accept="accept"
            multiple
            class="file-input"
            @change="onFileSelect"
        />

        <div class="gallery-grid">
            <!-- Existing images -->
            <div
                v-for="media in modelValue"
                :key="media.id"
                class="gallery-item"
                :class="{ 'is-deleting': deletingId === media.id }"
                draggable="true"
                @dragstart="onDragStart(media)"
                @dragover="(e) => onDragOver(e, media)"
                @dragend="onDragEnd"
            >
                <img
                    :src="media.thumbnail"
                    :alt="media.filename"
                    class="gallery-image"
                />
                <div class="item-overlay">
                    <button
                        type="button"
                        class="overlay-btn remove-btn"
                        @click="onRemove(media)"
                        title="Remove image"
                    >
                        <i class="pi pi-trash"></i>
                    </button>
                </div>
                <div class="drag-indicator">
                    <i class="pi pi-arrows-alt"></i>
                </div>
                <div v-if="deletingId === media.id" class="deleting-overlay">
                    <ProgressSpinner style="width: 24px; height: 24px" strokeWidth="3" />
                </div>
            </div>

            <!-- Videos -->
            <div
                v-for="video in videos"
                :key="`video-${video.id}`"
                class="gallery-item video-item"
            >
                <img
                    v-if="video.thumbnail_url"
                    :src="video.thumbnail_url"
                    :alt="video.title || 'Video'"
                    class="gallery-image"
                />
                <div v-else class="video-placeholder">
                    <i :class="getPlatformIcon(video.platform)"></i>
                </div>
                <div class="video-badge">
                    <i :class="getPlatformIcon(video.platform)"></i>
                </div>
                <div class="item-overlay">
                    <button
                        type="button"
                        class="overlay-btn remove-btn"
                        @click="removeVideo(video)"
                        title="Remove video"
                    >
                        <i class="pi pi-trash"></i>
                    </button>
                </div>
            </div>

            <!-- Upload slot -->
            <div
                v-if="canUpload"
                class="gallery-item upload-slot"
                :class="{ 'drag-over': isDragOver }"
                @click="triggerFileSelect"
                @dragenter="onDragEnter"
                @dragleave="onDragLeave"
                @dragover="onDragOverSlot"
                @drop="onDropFiles"
            >
                <div v-if="uploading" class="upload-loading">
                    <ProgressSpinner style="width: 24px; height: 24px" strokeWidth="3" />
                </div>
                <div v-else class="upload-placeholder">
                    <div class="placeholder-icon">
                        <i class="pi pi-plus"></i>
                    </div>
                    <span class="placeholder-text">Add Images</span>
                    <span class="placeholder-hint">{{ remainingSlots }} remaining</span>
                </div>
            </div>

            <!-- Add Video slot -->
            <div
                v-if="canAddVideo && !showVideoForm"
                class="gallery-item video-add-slot"
                @click="showVideoForm = true"
            >
                <div class="upload-placeholder">
                    <div class="placeholder-icon video-icon">
                        <i class="pi pi-video"></i>
                    </div>
                    <span class="placeholder-text">Add Video</span>
                    <span class="placeholder-hint">YouTube or Vimeo</span>
                </div>
            </div>
        </div>

        <!-- Video URL Input Form -->
        <div v-if="showVideoForm" class="video-form">
            <div class="video-form-header">
                <span class="video-form-title">Add Video</span>
                <button
                    type="button"
                    class="video-form-close"
                    @click="showVideoForm = false; videoUrl = ''; videoError = null"
                >
                    <i class="pi pi-times"></i>
                </button>
            </div>
            <div class="video-form-body">
                <InputText
                    v-model="videoUrl"
                    placeholder="Paste YouTube or Vimeo URL..."
                    class="video-url-input"
                    @keyup.enter="addVideo"
                />
                <button
                    type="button"
                    class="video-add-btn"
                    :disabled="!videoUrl.trim() || addingVideo"
                    @click="addVideo"
                >
                    <ProgressSpinner v-if="addingVideo" style="width: 16px; height: 16px" strokeWidth="4" />
                    <i v-else class="pi pi-plus"></i>
                </button>
            </div>
            <small v-if="videoError" class="video-error">
                <i class="pi pi-exclamation-circle"></i>
                {{ videoError }}
            </small>
        </div>

        <small v-if="error" class="error-text">
            <i class="pi pi-exclamation-circle"></i>
            {{ error }}
        </small>
        <small class="help-text">
            <i class="pi pi-info-circle"></i>
            Drag images to reorder. Maximum {{ max }} images{{ showVideos ? ` and ${maxVids} videos` : '' }}.
        </small>
    </div>
</template>

<style scoped>
.gallery-upload {
    width: 100%;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.file-input {
    display: none;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: 0.75rem;
}

@media (min-width: 640px) {
    .gallery-grid {
        grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
    }
}

.gallery-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 0.75rem;
    overflow: hidden;
    background: var(--color-slate-100, #f1f5f9);
    border: 2px solid var(--color-slate-200, #e2e8f0);
    cursor: grab;
    transition: all 0.2s ease;
}

.gallery-item:hover {
    border-color: var(--color-slate-300, #cbd5e1);
}

.gallery-item:active {
    cursor: grabbing;
}

.gallery-item.is-deleting {
    opacity: 0.6;
    pointer-events: none;
}

.gallery-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.item-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.gallery-item:hover .item-overlay {
    opacity: 1;
}

.overlay-btn {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.remove-btn {
    background: #ef4444;
    color: white;
}

.remove-btn:hover {
    background: #dc2626;
}

.drag-indicator {
    position: absolute;
    bottom: 4px;
    right: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.6);
    color: white;
    border-radius: 0.375rem;
    font-size: 0.625rem;
    opacity: 0;
    transition: opacity 0.2s;
}

.gallery-item:hover .drag-indicator {
    opacity: 1;
}

.deleting-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.8);
}

/* Video items */
.video-item {
    cursor: default;
}

.video-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-slate-200, #e2e8f0);
    color: var(--color-slate-400, #94a3b8);
}

.video-placeholder i {
    font-size: 1.5rem;
}

.video-badge {
    position: absolute;
    top: 4px;
    left: 4px;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.7);
    color: white;
    border-radius: 0.375rem;
    font-size: 0.75rem;
}

/* Upload slot */
.upload-slot,
.video-add-slot {
    display: flex;
    align-items: center;
    justify-content: center;
    border-style: dashed;
    cursor: pointer;
}

.upload-slot:hover,
.video-add-slot:hover {
    background: var(--color-slate-50, #f8fafc);
    border-color: var(--color-slate-300, #cbd5e1);
}

.upload-slot.drag-over {
    border-color: #4f46e5;
    background: #eef2ff;
}

.upload-loading {
    display: flex;
    align-items: center;
    justify-content: center;
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem;
    text-align: center;
}

.placeholder-icon {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    color: var(--color-slate-400, #94a3b8);
}

.placeholder-icon i {
    font-size: 0.875rem;
}

.placeholder-icon.video-icon {
    background: #eef2ff;
    color: #4f46e5;
}

.placeholder-text {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.placeholder-hint {
    font-size: 0.625rem;
    color: var(--color-slate-500, #64748b);
}

/* Video form */
.video-form {
    background: var(--color-slate-50, #f8fafc);
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
    overflow: hidden;
}

.video-form-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--color-slate-200, #e2e8f0);
}

.video-form-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.video-form-close {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    color: var(--color-slate-400, #94a3b8);
    cursor: pointer;
    transition: all 0.15s ease;
}

.video-form-close:hover {
    background: var(--color-slate-200, #e2e8f0);
    color: var(--color-slate-600, #475569);
}

.video-form-body {
    display: flex;
    gap: 0.5rem;
    padding: 1rem;
}

.video-url-input {
    flex: 1;
    height: 40px;
}

.video-add-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #4f46e5;
    color: white;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.video-add-btn:hover:not(:disabled) {
    background: #4338ca;
}

.video-add-btn:disabled {
    background: var(--color-slate-300, #cbd5e1);
    cursor: not-allowed;
}

.video-error {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0 1rem 1rem;
    color: #ef4444;
    font-size: 0.8125rem;
}

.video-error i {
    font-size: 0.75rem;
}

/* Helper text */
.error-text {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    color: #ef4444;
    font-size: 0.8125rem;
}

.error-text i {
    font-size: 0.75rem;
}

.help-text {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: var(--color-slate-500, #64748b);
    font-size: 0.75rem;
}

.help-text i {
    font-size: 0.75rem;
    color: var(--color-slate-400, #94a3b8);
}
</style>
