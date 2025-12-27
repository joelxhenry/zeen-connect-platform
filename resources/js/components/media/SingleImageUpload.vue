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
    filename: string;
}

interface MediaResponse {
    media: MediaItem;
}

const props = defineProps<{
    modelValue?: MediaItem | null;
    uploadUrl: string;
    collection: string;
    maxFileSize?: number;
    acceptedTypes?: string;
    placeholder?: string;
    shape?: 'square' | 'circle' | 'cover';
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: MediaItem | null): void;
    (e: 'uploaded', media: MediaItem): void;
    (e: 'deleted'): void;
    (e: 'error', message: string): void;
}>();

const api = useApi<MediaResponse>({ showErrorToast: false });
const uploading = ref(false);
const error = ref<string | null>(null);

const maxSize = computed(() => props.maxFileSize || 10485760); // 10MB default
const accept = computed(() => props.acceptedTypes || 'image/jpeg,image/png,image/gif,image/webp');

const previewUrl = computed(() => {
    if (props.modelValue) {
        return props.shape === 'cover' ? props.modelValue.medium : props.modelValue.thumbnail;
    }
    return null;
});

const containerClass = computed(() => {
    const base = 'upload-container';
    if (props.shape === 'circle') return `${base} circle`;
    if (props.shape === 'cover') return `${base} cover`;
    return `${base} square`;
});

const onUpload = async (event: any) => {
    const file = event.files[0];
    if (!file) return;

    uploading.value = true;
    error.value = null;

    const formData = new FormData();
    formData.append('file', file);
    formData.append('collection', props.collection);

    try {
        const result = await api.upload<MediaResponse>(props.uploadUrl, formData);
        emit('update:modelValue', result.media);
        emit('uploaded', result.media);
    } catch (e) {
        const message = e instanceof ApiError ? e.message : 'Upload failed';
        error.value = message;
        emit('error', message);
    } finally {
        uploading.value = false;
    }
};

const onRemove = async () => {
    if (!props.modelValue) return;

    try {
        await api.delete(`/media/${props.modelValue.id}`);
        emit('update:modelValue', null);
        emit('deleted');
    } catch (e) {
        const message = e instanceof ApiError ? e.message : 'Delete failed';
        error.value = message;
        emit('error', message);
    }
};
</script>

<template>
    <div :class="containerClass">
        <div v-if="uploading" class="upload-loading">
            <ProgressSpinner style="width: 40px; height: 40px" />
        </div>

        <template v-else-if="previewUrl">
            <div class="preview-wrapper">
                <Image
                    :src="previewUrl"
                    :alt="modelValue?.filename || 'Uploaded image'"
                    preview
                    class="preview-image"
                />
                <Button
                    icon="pi pi-times"
                    severity="danger"
                    rounded
                    class="remove-btn"
                    @click="onRemove"
                    size="small"
                    text
                />
            </div>
        </template>

        <template v-else>
            <FileUpload
                mode="basic"
                :auto="true"
                :accept="accept"
                :maxFileSize="maxSize"
                :customUpload="true"
                @uploader="onUpload"
                chooseLabel=""
                class="upload-btn"
            >
                <template #header>
                    <div class="upload-placeholder">
                        <i class="pi pi-camera"></i>
                        <span>{{ placeholder || 'Upload Image' }}</span>
                    </div>
                </template>
            </FileUpload>
        </template>

        <small v-if="error" class="error-text">{{ error }}</small>
    </div>
</template>

<style scoped>
.upload-container {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--p-surface-100);
    border: 2px dashed var(--p-surface-300);
    overflow: hidden;
    transition: all 0.2s ease;
}

.upload-container:hover {
    border-color: var(--p-primary-color);
}

.upload-container.square {
    width: 150px;
    height: 150px;
    border-radius: 8px;
}

.upload-container.circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
}

.upload-container.cover {
    width: 100%;
    height: 200px;
    border-radius: 12px;
}

.upload-loading {
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.preview-wrapper :deep(.p-image) {
    width: 100%;
    height: 100%;
}

.preview-wrapper :deep(.p-image img) {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.remove-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    z-index: 10;
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
    gap: 8px;
    color: var(--p-text-muted-color);
}

.upload-placeholder i {
    font-size: 24px;
}

.upload-placeholder span {
    font-size: 12px;
}

.error-text {
    position: absolute;
    bottom: -20px;
    left: 0;
    color: var(--p-red-500);
}
</style>
