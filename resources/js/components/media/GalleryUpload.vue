<script setup lang="ts">
import { ref, computed } from 'vue';
import FileUpload from 'primevue/fileupload';
import Button from 'primevue/button';
import Image from 'primevue/image';
import ProgressSpinner from 'primevue/progressspinner';
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

interface MediaListResponse {
    media: MediaItem[];
}

const props = defineProps<{
    modelValue: MediaItem[];
    uploadUrl: string;
    deleteUrl?: string;
    collection?: string;
    maxFiles?: number;
    maxFileSize?: number;
    acceptedTypes?: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: MediaItem[]): void;
    (e: 'uploaded', media: MediaItem): void;
    (e: 'deleted', id: number): void;
    (e: 'reordered', ids: number[]): void;
    (e: 'error', message: string): void;
}>();

const api = useApi({ showErrorToast: false });
const uploading = ref(false);
const error = ref<string | null>(null);
const draggedItem = ref<MediaItem | null>(null);

const maxSize = computed(() => props.maxFileSize || 10485760); // 10MB default
const accept = computed(() => props.acceptedTypes || 'image/jpeg,image/png,image/gif,image/webp');
const max = computed(() => props.maxFiles || 5);

const canUpload = computed(() => props.modelValue.length < max.value);
const remainingSlots = computed(() => max.value - props.modelValue.length);

const onUpload = async (event: any) => {
    const files = event.files;
    if (!files || files.length === 0) return;

    uploading.value = true;
    error.value = null;

    const formData = new FormData();
    files.forEach((file: File) => {
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
</script>

<template>
    <div class="gallery-upload">
        <div class="gallery-grid">
            <!-- Existing images -->
            <div
                v-for="media in modelValue"
                :key="media.id"
                class="gallery-item"
                draggable="true"
                @dragstart="onDragStart(media)"
                @dragover="(e) => onDragOver(e, media)"
                @dragend="onDragEnd"
            >
                <Image
                    :src="media.thumbnail"
                    :alt="media.filename"
                    preview
                    :pt="{
                        image: { class: 'gallery-image' }
                    }"
                />
                <Button
                    icon="pi pi-times"
                    severity="danger"
                    rounded
                    size="small"
                    class="remove-btn"
                    @click="onRemove(media)"
                />
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
                            <span>{{ remainingSlots }} left</span>
                        </div>
                    </template>
                </FileUpload>
            </div>
        </div>

        <small v-if="error" class="error-text">{{ error }}</small>
        <small class="help-text">Drag to reorder. Maximum {{ max }} images.</small>
    </div>
</template>

<style scoped>
.gallery-upload {
    width: 100%;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
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

.remove-btn {
    position: absolute;
    top: 4px;
    right: 4px;
    z-index: 10;
    opacity: 0;
    transition: opacity 0.2s;
}

.gallery-item:hover .remove-btn {
    opacity: 1;
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
    font-size: 20px;
}

.upload-placeholder span {
    font-size: 11px;
}

.error-text {
    display: block;
    margin-top: 8px;
    color: var(--p-red-500);
}

.help-text {
    display: block;
    margin-top: 8px;
    color: var(--p-text-muted-color);
}
</style>
