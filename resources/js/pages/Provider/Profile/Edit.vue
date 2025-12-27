<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, usePage } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import GalleryUpload from '@/components/media/GalleryUpload.vue';
import VideoEmbedForm from '@/components/media/VideoEmbedForm.vue';
import ProfileController from '@/actions/App/Domains/Provider/Controllers/ProfileController';
import type { Provider, MediaItem, VideoEmbed } from '@/types/models';

interface Props {
    provider: Provider & {
        avatar?: MediaItem | null;
        cover?: MediaItem | null;
        gallery?: MediaItem[];
        videos?: VideoEmbed[];
    };
}

const props = defineProps<Props>();
const toast = useToast();
const page = usePage();

// Media state - separate from form to handle uploads independently
const avatar = ref<MediaItem | null>(props.provider.avatar || null);
const coverPhoto = ref<MediaItem | null>(props.provider.cover || null);
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
            <div class="mb-6">
                <h1 class="text-xl lg:text-2xl font-semibold text-[#0D1F1B] m-0 mb-1">
                    Edit Profile
                </h1>
                <p class="text-gray-500 m-0 text-sm lg:text-base">
                    Update your business profile and media.
                </p>
            </div>

            <!-- Cover Photo Section -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                    <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                        <i class="pi pi-image text-[#106B4F]"></i>
                        Cover Photo
                    </h2>
                </div>
                <div class="p-4 lg:p-5">
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
                </div>
            </div>

            <!-- Avatar & Basic Info Section -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                    <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                        <i class="pi pi-user text-[#106B4F]"></i>
                        Profile Information
                    </h2>
                </div>
                <div class="p-4 lg:p-5">
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

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                        </div>
                    </div>
                </div>
            </div>

            <!-- Social Links Section -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                    <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                        <i class="pi pi-share-alt text-[#106B4F]"></i>
                        Social Media
                    </h2>
                </div>
                <div class="p-4 lg:p-5 space-y-4">
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
            </div>

            <!-- Gallery Section -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                    <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                        <i class="pi pi-images text-[#106B4F]"></i>
                        Photo Gallery
                    </h2>
                </div>
                <div class="p-4 lg:p-5">
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
                </div>
            </div>

            <!-- Videos Section -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                <div class="px-4 lg:px-5 py-3 lg:py-4 border-b border-gray-200">
                    <h2 class="text-sm lg:text-base font-semibold text-[#0D1F1B] m-0 flex items-center gap-2">
                        <i class="pi pi-video text-[#106B4F]"></i>
                        Videos
                    </h2>
                </div>
                <div class="p-4 lg:p-5">
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
                </div>
            </div>

            <!-- Save Button -->
            <div class="flex justify-end gap-3">
                <Button
                    label="Save Changes"
                    icon="pi pi-check"
                    type="button"
                    :loading="form.processing"
                    class="!bg-[#106B4F] !border-[#106B4F]"
                    @click="submit"
                />
            </div>
        </div>
    </ConsoleLayout>
</template>
