<script setup lang="ts">
import { ref, computed } from 'vue';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';
import Image from 'primevue/image';
import ProgressSpinner from 'primevue/progressspinner';
import Badge from 'primevue/badge';

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

const props = defineProps<{
    modelValue: MediaItem[];
    displayMediaId: number | null;
    serviceId: number;
    uploadUrl: string;
    deleteUrl?: string;
    setDisplayUrl: string;
    maxFiles?: number;
    maxFileSize?: number;
    acceptedTypes?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: MediaItem[]): void;
    (e: 'update:displayMediaId', value: number | null): void;
    (e: 'uploaded', media: MediaItem): void;
    (e: 'deleted', id: number): void;
    (e: 'displayChanged', id: number | null): void;
    (e: 'error', message: string): void;
}>();

const uploading = ref(false);
const settingDisplay = ref<number | null>(null);
const error = ref<string | null>(null);
const draggedItem = ref<MediaItem | null>(null);

const maxSize = computed(() => props.maxFileSize || 10485760);
const accept = computed(() => props.acceptedTypes || 'image/jpeg,image/png,image/gif,image/webp');
const max = computed(() => props.maxFiles || 5);

const canUpload = computed(() => props.modelValue.length < max.value);
const remainingSlots = computed(() => max.value - props.modelValue.length);

const isDisplayImage = (media: MediaItem) => {
    return props.displayMediaId === media.id;
};

const getCsrfToken = () => {
    return document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1]?.replace('%3D', '=') || '';
};

const onUpload = async (event: any) => {
    const files = event.files;
    if (!files || files.length === 0) return;

    uploading.value = true;
    error.value = null;

    const formData = new FormData();
    files.forEach((file: File) => {
        formData.append('files[]', file);
    });
    formData.append('collection', 'gallery');

    try {
        const response = await fetch(props.uploadUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            credentials: 'include',
        });

        const data = await response.json();

        if (data.success && data.media) {
            const newMedia = [...props.modelValue, ...data.media];
            emit('update:modelValue', newMedia);
            data.media.forEach((m: MediaItem) => emit('uploaded', m));

            // If this is the first image, set it as display
            if (props.modelValue.length === 0 && data.media.length > 0) {
                await setAsDisplay(data.media[0]);
            }
        } else {
            error.value = data.error || 'Upload failed';
            emit('error', error.value);
        }
    } catch (e: any) {
        error.value = e.message || 'Upload failed';
        emit('error', error.value);
    } finally {
        uploading.value = false;
    }
};

const onRemove = async (media: MediaItem) => {
    const deleteEndpoint = props.deleteUrl || `/media/${media.id}`;

    try {
        const response = await fetch(deleteEndpoint, {
            method: 'DELETE',
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            credentials: 'include',
        });

        const data = await response.json();

        if (data.success) {
            const newMedia = props.modelValue.filter(m => m.id !== media.id);
            emit('update:modelValue', newMedia);
            emit('deleted', media.id);

            // If we deleted the display image, clear it or set first available
            if (isDisplayImage(media)) {
                if (newMedia.length > 0) {
                    await setAsDisplay(newMedia[0]);
                } else {
                    emit('update:displayMediaId', null);
                    emit('displayChanged', null);
                }
            }
        }
    } catch (e: any) {
        error.value = e.message || 'Delete failed';
        emit('error', error.value);
    }
};

const setAsDisplay = async (media: MediaItem) => {
    settingDisplay.value = media.id;

    try {
        const response = await fetch(props.setDisplayUrl, {
            method: 'POST',
            body: JSON.stringify({ media_id: media.id }),
            headers: {
                'X-XSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'include',
        });

        const data = await response.json();

        if (data.success) {
            emit('update:displayMediaId', media.id);
            emit('displayChanged', media.id);
        } else {
            error.value = data.error || 'Failed to set display image';
            emit('error', error.value);
        }
    } catch (e: any) {
        error.value = e.message || 'Failed to set display image';
        emit('error', error.value);
    } finally {
        settingDisplay.value = null;
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
    draggedItem.value = null;
};
</script>

<template>
    <div class="service-gallery-upload">
        <div class="gallery-grid">
            <!-- Existing images -->
            <div
                v-for="media in modelValue"
                :key="media.id"
                class="gallery-item"
                :class="{ 'is-display': isDisplayImage(media) }"
                draggable="true"
                @dragstart="onDragStart(media)"
                @dragover="(e) => onDragOver(e, media)"
                @dragend="onDragEnd"
            >
                <!-- Display badge -->
                <div v-if="isDisplayImage(media)" class="display-badge">
                    <i class="pi pi-star-fill"></i>
                    <span>Display</span>
                </div>

                <Image
                    :src="media.thumbnail"
                    :alt="media.filename"
                    preview
                    :pt="{
                        image: { class: 'gallery-image' }
                    }"
                />

                <!-- Actions overlay -->
                <div class="actions-overlay">
                    <Button
                        v-if="!isDisplayImage(media)"
                        icon="pi pi-star"
                        severity="secondary"
                        rounded
                        size="small"
                        class="action-btn"
                        @click="setAsDisplay(media)"
                        :loading="settingDisplay === media.id"
                        v-tooltip.top="'Set as display image'"
                    />
                    <Button
                        icon="pi pi-times"
                        severity="danger"
                        rounded
                        size="small"
                        class="action-btn"
                        @click="onRemove(media)"
                        v-tooltip.top="'Delete'"
                    />
                </div>

                <div class="drag-handle">
                    <i class="pi pi-bars"></i>
                </div>
            </div>

            <!-- Upload slot -->
            <div v-if="canUpload" class="gallery-item upload-slot">
                <div v-if="uploading" class="upload-loading">
                    <ProgressSpinner style="width: 30px; height: 30px" />
                </div>
                <FileUpload
                    v-else
                    mode="basic"
                    :auto="true"
                    :multiple="true"
                    :accept="accept"
                    :maxFileSize="maxSize"
                    :customUpload="true"
                    @uploader="onUpload"
                    chooseLabel=""
                    class="upload-btn"
                >
                    <template #header>
                        <div class="upload-placeholder">
                            <i class="pi pi-plus"></i>
                            <span>Add Images</span>
                            <small>{{ remainingSlots }} left</small>
                        </div>
                    </template>
                </FileUpload>
            </div>
        </div>

        <small v-if="error" class="error-text">{{ error }}</small>
        <div class="help-section">
            <small class="help-text">
                <i class="pi pi-star-fill text-yellow-500"></i>
                Click the star to set as display image. Drag to reorder.
            </small>
            <small class="help-text">Maximum {{ max }} images. The display image will be shown in listings.</small>
        </div>
    </div>
</template>

<style scoped>
.service-gallery-upload {
    width: 100%;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
    gap: 12px;
}

.gallery-item {
    position: relative;
    aspect-ratio: 1;
    border-radius: 8px;
    overflow: hidden;
    background: var(--p-surface-100);
    border: 2px solid var(--p-surface-200);
    cursor: grab;
    transition: all 0.2s ease;
}

.gallery-item:hover {
    border-color: var(--p-primary-color);
}

.gallery-item.is-display {
    border-color: #f59e0b;
    border-width: 3px;
}

.gallery-item:active {
    cursor: grabbing;
}

.gallery-item :deep(.p-image) {
    width: 100%;
    height: 100%;
}

.gallery-item :deep(.gallery-image) {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.display-badge {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to bottom, rgba(245, 158, 11, 0.9), transparent);
    color: white;
    padding: 4px 8px;
    font-size: 10px;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 4px;
    z-index: 5;
}

.display-badge i {
    font-size: 10px;
}

.actions-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    display: flex;
    gap: 8px;
    opacity: 0;
    transition: opacity 0.2s;
    z-index: 10;
}

.gallery-item:hover .actions-overlay {
    opacity: 1;
}

.action-btn {
    background: rgba(255, 255, 255, 0.9) !important;
}

.drag-handle {
    position: absolute;
    bottom: 4px;
    right: 4px;
    background: rgba(0, 0, 0, 0.5);
    color: white;
    padding: 2px 6px;
    border-radius: 4px;
    font-size: 10px;
    opacity: 0;
    transition: opacity 0.2s;
}

.gallery-item:hover .drag-handle {
    opacity: 1;
}

.upload-slot {
    display: flex;
    align-items: center;
    justify-content: center;
    border-style: dashed;
    cursor: pointer;
}

.upload-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
}

.upload-btn {
    width: 100%;
    height: 100%;
}

.upload-btn :deep(.p-button) {
    width: 100%;
    height: 100%;
    background: transparent;
    border: none;
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    color: var(--p-text-muted-color);
}

.upload-placeholder i {
    font-size: 24px;
}

.upload-placeholder span {
    font-size: 12px;
    font-weight: 500;
}

.upload-placeholder small {
    font-size: 10px;
}

.error-text {
    display: block;
    margin-top: 8px;
    color: var(--p-red-500);
}

.help-section {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.help-text {
    display: flex;
    align-items: center;
    gap: 4px;
    color: var(--p-text-muted-color);
}
</style>
