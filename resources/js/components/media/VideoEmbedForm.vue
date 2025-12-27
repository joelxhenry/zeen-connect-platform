<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import Message from 'primevue/message';

interface VideoEmbed {
    id: number;
    uuid: string;
    platform: string;
    video_id: string;
    url: string;
    embed_url: string;
    embed_code: string;
    title: string | null;
    thumbnail_url: string | null;
    order: number;
}

const props = defineProps<{
    modelValue: VideoEmbed[];
    addUrl: string;
    deleteUrl?: string;
    maxVideos?: number;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: VideoEmbed[]): void;
    (e: 'added', video: VideoEmbed): void;
    (e: 'deleted', id: number): void;
    (e: 'error', message: string): void;
}>();

const activeTab = ref(0);
const videoUrl = ref('');
const embedCode = ref('');
const videoTitle = ref('');
const loading = ref(false);
const error = ref<string | null>(null);
const success = ref<string | null>(null);

const max = computed(() => props.maxVideos || 5);
const canAdd = computed(() => props.modelValue.length < max.value);

watch([videoUrl, embedCode], () => {
    error.value = null;
    success.value = null;
});

const addVideo = async () => {
    if (!canAdd.value) return;

    const payload: any = {
        title: videoTitle.value || null,
    };

    if (activeTab.value === 0 && videoUrl.value) {
        payload.url = videoUrl.value;
    } else if (activeTab.value === 1 && embedCode.value) {
        payload.embed_code = embedCode.value;
    } else {
        error.value = 'Please provide a video URL or embed code';
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        const response = await fetch(props.addUrl, {
            method: 'POST',
            body: JSON.stringify(payload),
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

        const data = await response.json();

        if (data.success && data.video) {
            const newVideos = [...props.modelValue, data.video];
            emit('update:modelValue', newVideos);
            emit('added', data.video);

            // Clear form
            videoUrl.value = '';
            embedCode.value = '';
            videoTitle.value = '';
            success.value = 'Video added successfully';
        } else {
            error.value = data.error || 'Failed to add video';
            emit('error', error.value);
        }
    } catch (e: any) {
        error.value = e.message || 'Failed to add video';
        emit('error', error.value);
    } finally {
        loading.value = false;
    }
};

const removeVideo = async (video: VideoEmbed) => {
    const deleteEndpoint = props.deleteUrl || `/videos/${video.id}`;

    try {
        const response = await fetch(deleteEndpoint, {
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
            const newVideos = props.modelValue.filter(v => v.id !== video.id);
            emit('update:modelValue', newVideos);
            emit('deleted', video.id);
        }
    } catch (e: any) {
        error.value = e.message || 'Delete failed';
        emit('error', error.value);
    }
};

const getPlatformIcon = (platform: string) => {
    return platform === 'youtube' ? 'pi pi-youtube' : 'pi pi-video';
};
</script>

<template>
    <div class="video-embed-form">
        <!-- Existing videos -->
        <div v-if="modelValue.length > 0" class="video-list">
            <div v-for="video in modelValue" :key="video.id" class="video-item">
                <div class="video-thumbnail">
                    <img
                        v-if="video.thumbnail_url"
                        :src="video.thumbnail_url"
                        :alt="video.title || 'Video thumbnail'"
                    />
                    <div v-else class="video-placeholder">
                        <i :class="getPlatformIcon(video.platform)"></i>
                    </div>
                </div>
                <div class="video-info">
                    <span class="video-title">{{ video.title || video.platform }}</span>
                    <span class="video-platform">
                        <i :class="getPlatformIcon(video.platform)"></i>
                        {{ video.platform }}
                    </span>
                </div>
                <Button
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    @click="removeVideo(video)"
                />
            </div>
        </div>

        <!-- Add video form -->
        <div v-if="canAdd" class="add-video-form">
            <Message v-if="error" severity="error" :closable="false">{{ error }}</Message>
            <Message v-if="success" severity="success" :closable="false">{{ success }}</Message>

            <TabView v-model:activeIndex="activeTab">
                <TabPanel header="Video URL">
                    <div class="form-field">
                        <label for="videoUrl">YouTube or Vimeo URL</label>
                        <InputText
                            id="videoUrl"
                            v-model="videoUrl"
                            placeholder="https://www.youtube.com/watch?v=..."
                            class="w-full"
                        />
                    </div>
                </TabPanel>
                <TabPanel header="Embed Code">
                    <div class="form-field">
                        <label for="embedCode">Paste embed code</label>
                        <Textarea
                            id="embedCode"
                            v-model="embedCode"
                            placeholder="<iframe src=..."
                            rows="3"
                            class="w-full"
                        />
                    </div>
                </TabPanel>
            </TabView>

            <div class="form-field">
                <label for="videoTitle">Title (optional)</label>
                <InputText
                    id="videoTitle"
                    v-model="videoTitle"
                    placeholder="Video title"
                    class="w-full"
                />
            </div>

            <Button
                label="Add Video"
                icon="pi pi-plus"
                :loading="loading"
                @click="addVideo"
                :disabled="!canAdd || (!videoUrl && !embedCode)"
            />
        </div>

        <small v-if="!canAdd" class="limit-text">Maximum {{ max }} videos reached</small>
    </div>
</template>

<style scoped>
.video-embed-form {
    width: 100%;
}

.video-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 20px;
}

.video-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: var(--p-surface-50);
    border-radius: 8px;
    border: 1px solid var(--p-surface-200);
}

.video-thumbnail {
    width: 80px;
    height: 45px;
    border-radius: 4px;
    overflow: hidden;
    flex-shrink: 0;
}

.video-thumbnail img {
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
    color: var(--p-text-muted-color);
}

.video-placeholder i {
    font-size: 20px;
}

.video-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.video-title {
    font-weight: 500;
    color: var(--p-text-color);
}

.video-platform {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--p-text-muted-color);
    text-transform: capitalize;
}

.add-video-form {
    padding: 16px;
    background: var(--p-surface-50);
    border-radius: 8px;
    border: 1px solid var(--p-surface-200);
}

.form-field {
    margin-bottom: 16px;
}

.form-field label {
    display: block;
    margin-bottom: 4px;
    font-size: 14px;
    font-weight: 500;
    color: var(--p-text-color);
}

.limit-text {
    display: block;
    margin-top: 8px;
    color: var(--p-text-muted-color);
}
</style>
