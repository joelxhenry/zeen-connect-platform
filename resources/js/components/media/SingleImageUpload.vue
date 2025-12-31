<script setup lang="ts">
import { ref, computed } from 'vue';
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
const deleting = ref(false);
const error = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);
const isDragOver = ref(false);

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
    const shape = props.shape === 'circle' ? 'circle' : props.shape === 'cover' ? 'cover' : 'square';
    const drag = isDragOver.value ? 'drag-over' : '';
    return `${base} ${shape} ${drag}`.trim();
});

const triggerFileSelect = () => {
    if (!uploading.value && !deleting.value) {
        fileInput.value?.click();
    }
};

const onFileSelect = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];
    if (file) {
        await uploadFile(file);
    }
    // Reset input so same file can be selected again
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

const onDragOver = (e: DragEvent) => {
    e.preventDefault();
};

const onDrop = async (e: DragEvent) => {
    e.preventDefault();
    isDragOver.value = false;

    const file = e.dataTransfer?.files?.[0];
    if (file && file.type.startsWith('image/')) {
        await uploadFile(file);
    }
};

const uploadFile = async (file: File) => {
    if (file.size > maxSize.value) {
        error.value = 'File too large. Maximum size is 10MB.';
        emit('error', error.value);
        return;
    }

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

const onRemove = async (event: Event) => {
    event.stopPropagation();
    if (!props.modelValue || deleting.value) return;

    deleting.value = true;
    try {
        await api.delete(`/media/${props.modelValue.id}`);
        emit('update:modelValue', null);
        emit('deleted');
    } catch (e) {
        const message = e instanceof ApiError ? e.message : 'Delete failed';
        error.value = message;
        emit('error', message);
    } finally {
        deleting.value = false;
    }
};
</script>

<template>
    <div class="single-image-upload">
        <div
            :class="containerClass"
            @click="triggerFileSelect"
            @dragenter="onDragEnter"
            @dragleave="onDragLeave"
            @dragover="onDragOver"
            @drop="onDrop"
        >
            <input
                ref="fileInput"
                type="file"
                :accept="accept"
                class="file-input"
                @change="onFileSelect"
            />

            <!-- Loading State -->
            <div v-if="uploading || deleting" class="upload-loading">
                <ProgressSpinner style="width: 32px; height: 32px" strokeWidth="3" />
                <span class="loading-text">{{ deleting ? 'Removing...' : 'Uploading...' }}</span>
            </div>

            <!-- Preview -->
            <template v-else-if="previewUrl">
                <div class="preview-wrapper">
                    <img
                        :src="previewUrl"
                        :alt="modelValue?.filename || 'Uploaded image'"
                        class="preview-image"
                    />
                    <div class="preview-overlay">
                        <button
                            type="button"
                            class="overlay-btn change-btn"
                            @click.stop="triggerFileSelect"
                            title="Change image"
                        >
                            <i class="pi pi-camera"></i>
                        </button>
                        <button
                            type="button"
                            class="overlay-btn remove-btn"
                            @click="onRemove"
                            title="Remove image"
                        >
                            <i class="pi pi-trash"></i>
                        </button>
                    </div>
                </div>
            </template>

            <!-- Empty State -->
            <template v-else>
                <div class="upload-placeholder">
                    <div class="placeholder-icon">
                        <i class="pi pi-image"></i>
                    </div>
                    <span class="placeholder-text">{{ placeholder || 'Upload Image' }}</span>
                    <span class="placeholder-hint">Click or drag to upload</span>
                </div>
            </template>
        </div>

        <small v-if="error" class="error-text">
            <i class="pi pi-exclamation-circle"></i>
            {{ error }}
        </small>
    </div>
</template>

<style scoped>
.single-image-upload {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.upload-container {
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-slate-50, #f8fafc);
    border: 2px dashed var(--color-slate-200, #e2e8f0);
    overflow: hidden;
    transition: all 0.2s ease;
    cursor: pointer;
}

.upload-container:hover {
    border-color: var(--color-slate-300, #cbd5e1);
    background: var(--color-slate-100, #f1f5f9);
}

.upload-container.drag-over {
    border-color: #4f46e5;
    background: #eef2ff;
}

.upload-container.square {
    width: 150px;
    height: 150px;
    border-radius: 0.75rem;
}

.upload-container.circle {
    width: 120px;
    height: 120px;
    border-radius: 50%;
}

.upload-container.cover {
    width: 100%;
    height: 180px;
    border-radius: 0.75rem;
}

.file-input {
    display: none;
}

.upload-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.loading-text {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.preview-wrapper {
    position: relative;
    width: 100%;
    height: 100%;
}

.preview-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-overlay {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: rgba(0, 0, 0, 0.5);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.preview-wrapper:hover .preview-overlay {
    opacity: 1;
}

.overlay-btn {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.change-btn {
    background: white;
    color: var(--color-slate-700, #334155);
}

.change-btn:hover {
    background: var(--color-slate-100, #f1f5f9);
}

.remove-btn {
    background: #ef4444;
    color: white;
}

.remove-btn:hover {
    background: #dc2626;
}

.upload-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1rem;
    text-align: center;
}

.placeholder-icon {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--color-slate-100, #f1f5f9);
    border-radius: 0.75rem;
    color: var(--color-slate-400, #94a3b8);
}

.placeholder-icon i {
    font-size: 1.25rem;
}

.placeholder-text {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.placeholder-hint {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

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

/* Circle variant adjustments */
.upload-container.circle .placeholder-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.upload-container.circle .placeholder-text {
    font-size: 0.75rem;
}

.upload-container.circle .placeholder-hint {
    display: none;
}
</style>
