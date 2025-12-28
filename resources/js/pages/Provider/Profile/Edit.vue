<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsolePageHeader,
    ConsoleFormCard,
    ConsoleFormSection,
    ConsoleButton,
} from '@/components/console';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import { useToast } from 'primevue/usetoast';
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import GalleryUpload from '@/components/media/GalleryUpload.vue';
import VideoEmbedForm from '@/components/media/VideoEmbedForm.vue';
import ProfileController from '@/actions/App/Domains/Provider/Controllers/ProfileController';
import type { Provider, MediaItem, VideoEmbed } from '@/types/models';

interface Props {
    provider: Provider & {
        avatar_media?: MediaItem | null;
        cover_media?: MediaItem | null;
        gallery?: MediaItem[];
        videos?: VideoEmbed[];
    };
}

const props = defineProps<Props>();
const toast = useToast();
const page = usePage();

// Media state - separate from form to handle uploads independently
const avatar = ref<MediaItem | null>(props.provider.avatar_media || null);
const coverPhoto = ref<MediaItem | null>(props.provider.cover_media || null);
const gallery = ref<MediaItem[]>(props.provider.gallery || []);
const videos = ref<VideoEmbed[]>(props.provider.videos || []);

// Form for profile data (text fields)
const form = useForm({
    business_name: props.provider.business_name || '',
    tagline: props.provider.tagline || '',
    bio: props.provider.bio || '',
    address: props.provider.address || '',
    website: props.provider.website || '',
    social_links: {
        facebook: props.provider.social_links?.facebook || '',
        instagram: props.provider.social_links?.instagram || '',
        twitter: props.provider.social_links?.twitter || '',
    },
});

const baseUrl = computed(() => {
    return (page.props as any).ziggy?.url || window.location.origin;
});

const uploadUrl = computed(() => `${baseUrl.value}/media/upload`);
const uploadMultipleUrl = computed(() => `${baseUrl.value}/media/upload-multiple`);
const addVideoUrl = computed(() => `${baseUrl.value}/videos/provider`);

const submit = () => {
    form.put(ProfileController.update().url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: 'success',
                summary: 'Success',
                detail: 'Profile updated successfully',
                life: 3000,
            });
        },
        onError: () => {
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: 'Failed to update profile',
                life: 3000,
            });
        },
    });
};

const handleMediaUploaded = (type: string, media: MediaItem) => {
    toast.add({
        severity: 'success',
        summary: 'Success',
        detail: `${type} uploaded successfully`,
        life: 3000,
    });
};

const handleMediaError = (error: string) => {
    toast.add({
        severity: 'error',
        summary: 'Upload Error',
        detail: error,
        life: 5000,
    });
};
</script>

<template>
    <ConsoleLayout title="Edit Profile">
        <div class="w-full max-w-3xl mx-auto">
            <!-- Page Header -->
            <ConsolePageHeader
                title="Edit Profile"
                subtitle="Update your business profile and media"
            />

            <div class="space-y-6">
                <!-- Cover Photo Section -->
                <ConsoleFormCard title="Cover Photo" icon="pi pi-image">
                    <SingleImageUpload
                        v-model="coverPhoto"
                        :upload-url="uploadUrl"
                        collection="cover"
                        shape="cover"
                        placeholder="Upload Cover Photo"
                        @uploaded="(m) => handleMediaUploaded('Cover photo', m)"
                        @error="handleMediaError"
                    />
                    <small class="text-gray-500 mt-2 block">
                        Recommended size: 1200 x 300 pixels. Max 10MB.
                    </small>
                </ConsoleFormCard>

                <!-- Avatar & Basic Info Section -->
                <ConsoleFormCard title="Profile Information" icon="pi pi-user">
                    <div class="flex flex-col sm:flex-row gap-6">
                        <!-- Avatar Upload -->
                        <div class="flex-shrink-0">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Profile Photo
                            </label>
                            <SingleImageUpload
                                v-model="avatar"
                                :upload-url="uploadUrl"
                                collection="avatar"
                                shape="circle"
                                placeholder="Upload"
                                @uploaded="(m) => handleMediaUploaded('Avatar', m)"
                                @error="handleMediaError"
                            />
                        </div>

                        <!-- Basic Fields -->
                        <div class="flex-1 space-y-4">
                            <div>
                                <label for="business_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Business Name *
                                </label>
                                <InputText
                                    id="business_name"
                                    v-model="form.business_name"
                                    class="w-full"
                                    :class="{ 'p-invalid': form.errors.business_name }"
                                />
                                <small v-if="form.errors.business_name" class="text-red-500">
                                    {{ form.errors.business_name }}
                                </small>
                            </div>

                            <div>
                                <label for="tagline" class="block text-sm font-medium text-gray-700 mb-1">
                                    Tagline
                                </label>
                                <InputText
                                    id="tagline"
                                    v-model="form.tagline"
                                    class="w-full"
                                    placeholder="A short description of your business"
                                    :class="{ 'p-invalid': form.errors.tagline }"
                                />
                                <small v-if="form.errors.tagline" class="text-red-500">
                                    {{ form.errors.tagline }}
                                </small>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div>
                            <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">
                                About Your Business
                            </label>
                            <Textarea
                                id="bio"
                                v-model="form.bio"
                                rows="4"
                                class="w-full"
                                placeholder="Tell clients about your business, experience, and what makes you unique..."
                                :class="{ 'p-invalid': form.errors.bio }"
                            />
                            <small v-if="form.errors.bio" class="text-red-500">
                                {{ form.errors.bio }}
                            </small>
                        </div>

                        <ConsoleFormSection :columns="2">
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700 mb-1">
                                    Address
                                </label>
                                <InputText
                                    id="address"
                                    v-model="form.address"
                                    class="w-full"
                                    placeholder="Your business address"
                                />
                            </div>
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700 mb-1">
                                    Website
                                </label>
                                <InputText
                                    id="website"
                                    v-model="form.website"
                                    class="w-full"
                                    placeholder="https://yourwebsite.com"
                                />
                            </div>
                        </ConsoleFormSection>
                    </div>
                </ConsoleFormCard>

                <!-- Social Links Section -->
                <ConsoleFormCard title="Social Media" icon="pi pi-share-alt">
                    <div class="space-y-4">
                        <div>
                            <label for="facebook" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="pi pi-facebook mr-1"></i> Facebook
                            </label>
                            <InputText
                                id="facebook"
                                v-model="form.social_links.facebook"
                                class="w-full"
                                placeholder="https://facebook.com/yourbusiness"
                            />
                        </div>
                        <div>
                            <label for="instagram" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="pi pi-instagram mr-1"></i> Instagram
                            </label>
                            <InputText
                                id="instagram"
                                v-model="form.social_links.instagram"
                                class="w-full"
                                placeholder="https://instagram.com/yourbusiness"
                            />
                        </div>
                        <div>
                            <label for="twitter" class="block text-sm font-medium text-gray-700 mb-1">
                                <i class="pi pi-twitter mr-1"></i> Twitter / X
                            </label>
                            <InputText
                                id="twitter"
                                v-model="form.social_links.twitter"
                                class="w-full"
                                placeholder="https://twitter.com/yourbusiness"
                            />
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Gallery Section -->
                <ConsoleFormCard title="Photo Gallery" icon="pi pi-images">
                    <GalleryUpload
                        v-model="gallery"
                        :upload-url="uploadMultipleUrl"
                        collection="gallery"
                        :max-files="5"
                        @uploaded="(m) => handleMediaUploaded('Gallery image', m)"
                        @error="handleMediaError"
                    />
                    <small class="text-gray-500 mt-2 block">
                        Showcase your work with up to 5 photos. Drag to reorder.
                    </small>
                </ConsoleFormCard>

                <!-- Videos Section -->
                <ConsoleFormCard title="Videos" icon="pi pi-video">
                    <VideoEmbedForm
                        v-model="videos"
                        :add-url="addVideoUrl"
                        :max-videos="3"
                        @added="() => toast.add({ severity: 'success', summary: 'Success', detail: 'Video added', life: 3000 })"
                        @error="handleMediaError"
                    />
                    <small class="text-gray-500 mt-2 block">
                        Add YouTube or Vimeo videos to showcase your work.
                    </small>
                </ConsoleFormCard>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <ConsoleButton
                        label="Save Changes"
                        icon="pi pi-check"
                        :loading="form.processing"
                        @click="submit"
                    />
                </div>
            </div>
        </div>
    </ConsoleLayout>
</template>
