<script setup lang="ts">
import { ref, computed } from 'vue';
import { useForm, usePage, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import {
    ConsoleFormCard,
    ConsoleButton,
} from '@/components/console';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Button from 'primevue/button';
import { useToast } from 'primevue/usetoast';
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import GalleryUpload from '@/components/media/GalleryUpload.vue';
import VideoEmbedForm from '@/components/media/VideoEmbedForm.vue';
import ProfileController from '@/actions/App/Domains/Provider/Controllers/ProfileController';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
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
    domain: props.provider.domain || '',
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

// Check if form has changes
const hasChanges = computed(() => form.isDirty);

// Calculate profile completeness
const profileCompleteness = computed(() => {
    const fields = [
        { name: 'Business Name', filled: !!form.business_name },
        { name: 'Tagline', filled: !!form.tagline },
        { name: 'Bio', filled: !!form.bio },
        { name: 'Profile Photo', filled: !!avatar.value },
        { name: 'Cover Photo', filled: !!coverPhoto.value },
        { name: 'Address', filled: !!form.address },
    ];

    const filledCount = fields.filter(f => f.filled).length;
    const percentage = Math.round((filledCount / fields.length) * 100);
    const missing = fields.filter(f => !f.filled).map(f => f.name);

    return { percentage, missing, total: fields.length, filled: filledCount };
});

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

// Social link helpers
const hasSocialLinks = computed(() => {
    return form.social_links.facebook || form.social_links.instagram || form.social_links.twitter;
});
</script>

<template>
    <ConsoleLayout title="Edit Profile">
        <div class="profile-edit-page">
            <!-- Sticky Header -->
            <div class="sticky-header">
                <div class="header-content">
                    <div class="header-left">
                        <Button
                            icon="pi pi-arrow-left"
                            text
                            rounded
                            severity="secondary"
                            @click="router.visit(resolveUrl(provider.dashboard.url()))"
                            class="back-btn"
                        />
                        <div class="header-info">
                            <h1 class="header-title">Edit Profile</h1>
                            <p class="header-subtitle">{{ provider.business_name }}</p>
                        </div>
                    </div>
                    <div class="header-actions">
                        <ConsoleButton
                            label="Cancel"
                            variant="ghost"
                            :href="provider.dashboard.url()"
                            class="cancel-btn"
                        />
                        <ConsoleButton
                            label="Save Changes"
                            icon="pi pi-check"
                            @click="submit"
                            :loading="form.processing"
                        />
                    </div>
                </div>
                <div v-if="hasChanges && !form.processing" class="unsaved-indicator">
                    <i class="pi pi-circle-fill"></i>
                    Unsaved changes
                </div>
            </div>

            <!-- Main Content -->
            <form @submit.prevent="submit" class="main-content">
                <div class="content-grid">
                    <!-- Left Column - Main Form -->
                    <div class="form-column">
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
                            <div class="profile-info-grid">
                                <!-- Avatar Upload -->
                                <div class="avatar-section">
                                    <label class="field-label">Profile Photo</label>
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
                                <div class="basic-fields">
                                    <div class="field">
                                        <label for="business_name" class="field-label">
                                            Business Name <span class="required">*</span>
                                        </label>
                                        <InputText
                                            id="business_name"
                                            v-model="form.business_name"
                                            class="w-full"
                                            :class="{ 'p-invalid': form.errors.business_name }"
                                        />
                                        <small v-if="form.errors.business_name" class="field-error">
                                            {{ form.errors.business_name }}
                                        </small>
                                    </div>

                                    <div class="field">
                                        <label for="tagline" class="field-label">Tagline</label>
                                        <InputText
                                            id="tagline"
                                            v-model="form.tagline"
                                            class="w-full"
                                            placeholder="A short description of your business"
                                            :class="{ 'p-invalid': form.errors.tagline }"
                                        />
                                        <small v-if="form.errors.tagline" class="field-error">
                                            {{ form.errors.tagline }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="field mt-6">
                                <label for="bio" class="field-label">About Your Business</label>
                                <Textarea
                                    id="bio"
                                    v-model="form.bio"
                                    rows="4"
                                    class="w-full"
                                    placeholder="Tell clients about your business, experience, and what makes you unique..."
                                    :class="{ 'p-invalid': form.errors.bio }"
                                />
                                <small v-if="form.errors.bio" class="field-error">
                                    {{ form.errors.bio }}
                                </small>
                            </div>

                            <div class="field mt-6">
                                <label for="address" class="field-label">Address</label>
                                <InputText
                                    id="address"
                                    v-model="form.address"
                                    class="w-full"
                                    placeholder="Your business address"
                                />
                            </div>

                            <div class="field mt-4">
                                <label for="domain" class="field-label">
                                    Booking Site URL
                                    <span class="required">*</span>
                                </label>
                                <div class="domain-input">
                                    <InputText
                                        id="domain"
                                        v-model="form.domain"
                                        class="domain-field"
                                        placeholder="your-business"
                                        :class="{ 'p-invalid': form.errors.domain }"
                                    />
                                    <span class="domain-suffix">.zeen.com</span>
                                </div>
                                <small v-if="form.errors.domain" class="field-error">
                                    {{ form.errors.domain }}
                                </small>
                                <small v-else class="text-gray-500 mt-1 block">
                                    This is your unique booking page URL. Only lowercase letters, numbers, and hyphens allowed.
                                </small>
                            </div>
                        </ConsoleFormCard>

                        <!-- Social Links Section -->
                        <ConsoleFormCard title="Social Media" icon="pi pi-share-alt">
                            <div class="social-fields">
                                <div class="field">
                                    <label for="facebook" class="field-label">
                                        <i class="pi pi-facebook social-icon social-icon--facebook"></i>
                                        Facebook
                                    </label>
                                    <InputText
                                        id="facebook"
                                        v-model="form.social_links.facebook"
                                        class="w-full"
                                        placeholder="https://facebook.com/yourbusiness"
                                    />
                                </div>
                                <div class="field">
                                    <label for="instagram" class="field-label">
                                        <i class="pi pi-instagram social-icon social-icon--instagram"></i>
                                        Instagram
                                    </label>
                                    <InputText
                                        id="instagram"
                                        v-model="form.social_links.instagram"
                                        class="w-full"
                                        placeholder="https://instagram.com/yourbusiness"
                                    />
                                </div>
                                <div class="field">
                                    <label for="twitter" class="field-label">
                                        <i class="pi pi-twitter social-icon social-icon--twitter"></i>
                                        Twitter / X
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
                    </div>

                    <!-- Right Column - Sidebar -->
                    <div class="sidebar-column">
                        <!-- Quick Preview Card -->
                        <div class="preview-card">
                            <h3 class="preview-title">Profile Preview</h3>
                            <div class="preview-content">
                                <!-- Cover Preview -->
                                <div class="preview-cover" v-if="coverPhoto">
                                    <img :src="coverPhoto.url" alt="Cover" />
                                </div>
                                <div class="preview-cover preview-cover--empty" v-else>
                                    <i class="pi pi-image"></i>
                                </div>

                                <!-- Avatar Preview -->
                                <div class="preview-avatar-wrapper">
                                    <div class="preview-avatar" v-if="avatar">
                                        <img :src="avatar.url" alt="Avatar" />
                                    </div>
                                    <div class="preview-avatar preview-avatar--empty" v-else>
                                        <i class="pi pi-user"></i>
                                    </div>
                                </div>

                                <h4 class="preview-name">{{ form.business_name || 'Business Name' }}</h4>
                                <p class="preview-tagline" v-if="form.tagline">{{ form.tagline }}</p>

                                <div class="preview-social" v-if="hasSocialLinks">
                                    <a v-if="form.social_links.facebook" class="social-link">
                                        <i class="pi pi-facebook"></i>
                                    </a>
                                    <a v-if="form.social_links.instagram" class="social-link">
                                        <i class="pi pi-instagram"></i>
                                    </a>
                                    <a v-if="form.social_links.twitter" class="social-link">
                                        <i class="pi pi-twitter"></i>
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Completeness Card -->
                        <div class="sidebar-card">
                            <div class="sidebar-card-header">
                                <i class="pi pi-chart-pie"></i>
                                <span>Profile Completeness</span>
                            </div>
                            <div class="sidebar-card-content">
                                <div class="completeness-bar">
                                    <div
                                        class="completeness-fill"
                                        :style="{ width: `${profileCompleteness.percentage}%` }"
                                        :class="{
                                            'completeness-fill--low': profileCompleteness.percentage < 50,
                                            'completeness-fill--medium': profileCompleteness.percentage >= 50 && profileCompleteness.percentage < 100,
                                            'completeness-fill--full': profileCompleteness.percentage === 100,
                                        }"
                                    ></div>
                                </div>
                                <div class="completeness-text">
                                    <span class="completeness-percent">{{ profileCompleteness.percentage }}%</span>
                                    <span class="completeness-label">{{ profileCompleteness.filled }}/{{ profileCompleteness.total }} fields</span>
                                </div>
                                <div v-if="profileCompleteness.missing.length > 0" class="completeness-missing">
                                    <p class="missing-title">Missing:</p>
                                    <ul class="missing-list">
                                        <li v-for="field in profileCompleteness.missing" :key="field">{{ field }}</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Tips Card -->
                        <div class="sidebar-card">
                            <div class="sidebar-card-header">
                                <i class="pi pi-lightbulb"></i>
                                <span>Tips</span>
                            </div>
                            <div class="sidebar-card-content">
                                <ul class="tips-list">
                                    <li>
                                        <i class="pi pi-check-circle tip-icon"></i>
                                        <span>A complete profile builds trust with clients</span>
                                    </li>
                                    <li>
                                        <i class="pi pi-check-circle tip-icon"></i>
                                        <span>Add photos to showcase your work quality</span>
                                    </li>
                                    <li>
                                        <i class="pi pi-check-circle tip-icon"></i>
                                        <span>Link your social media for more visibility</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <!-- Media Stats Card -->
                        <div class="sidebar-card">
                            <div class="sidebar-card-header">
                                <i class="pi pi-images"></i>
                                <span>Media</span>
                            </div>
                            <div class="sidebar-card-content">
                                <div class="media-stats">
                                    <div class="media-stat">
                                        <span class="media-stat-value">{{ gallery.length }}/5</span>
                                        <span class="media-stat-label">Gallery Photos</span>
                                    </div>
                                    <div class="media-stat">
                                        <span class="media-stat-value">{{ videos.length }}/3</span>
                                        <span class="media-stat-label">Videos</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Mobile Save Bar -->
            <div class="mobile-save-bar">
                <ConsoleButton
                    label="Save Changes"
                    icon="pi pi-check"
                    @click="submit"
                    :loading="form.processing"
                    class="mobile-save-btn"
                />
            </div>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.profile-edit-page {
    max-width: 1200px;
    margin: 0 auto;
    padding-bottom: 80px;
}

/* Sticky Header */
.sticky-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    margin: -1.5rem -1.5rem 1.5rem;
    padding: 1rem 1.5rem;
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}

.back-btn {
    flex-shrink: 0;
}

.header-info {
    min-width: 0;
}

.header-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0;
    line-height: 1.3;
}

.header-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.header-actions {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

.cancel-btn {
    display: none;
}

.unsaved-indicator {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.75rem;
    color: #f59e0b;
    margin-top: 0.5rem;
}

.unsaved-indicator i {
    font-size: 0.5rem;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

.form-column {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.sidebar-column {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Form Fields */
.field {
    margin-bottom: 0;
}

.field-label {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 0.5rem;
}

.required {
    color: #ef4444;
}

.field-error {
    color: #ef4444;
    font-size: 0.75rem;
    margin-top: 0.25rem;
    display: block;
}

/* Profile Info Grid */
.profile-info-grid {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.avatar-section {
    flex-shrink: 0;
}

.basic-fields {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Domain Input */
.domain-input {
    display: flex;
    align-items: stretch;
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    overflow: hidden;
    background: white;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.domain-input:focus-within {
    border-color: #106B4F;
    box-shadow: 0 0 0 3px rgba(16, 107, 79, 0.1);
}

.domain-suffix {
    display: flex;
    align-items: center;
    padding: 0 0.75rem;
    background: #f3f4f6;
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
    border-left: 1px solid #d1d5db;
    white-space: nowrap;
}

.domain-field {
    flex: 1;
    border: none !important;
    border-radius: 0 !important;
    box-shadow: none !important;
}

.domain-field:focus {
    box-shadow: none !important;
}

/* Social Fields */
.social-fields {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.social-icon {
    font-size: 1rem;
}

.social-icon--facebook {
    color: #1877f2;
}

.social-icon--instagram {
    color: #e4405f;
}

.social-icon--twitter {
    color: #1da1f2;
}

/* Preview Card */
.preview-card {
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    border: 1px solid #d1fae5;
    border-radius: 1rem;
    padding: 1.25rem;
    overflow: hidden;
}

.preview-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #065f46;
    margin: 0 0 1rem;
}

.preview-content {
    text-align: center;
}

.preview-cover {
    width: calc(100% + 2.5rem);
    margin: -1.25rem -1.25rem 0;
    height: 80px;
    overflow: hidden;
    background: white;
}

.preview-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-cover--empty {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #9ca3af;
}

.preview-cover--empty i {
    font-size: 1.5rem;
}

.preview-avatar-wrapper {
    margin-top: -32px;
    position: relative;
    z-index: 1;
}

.preview-avatar {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto;
    border: 3px solid white;
    background: white;
}

.preview-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.preview-avatar--empty {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f3f4f6;
    color: #9ca3af;
}

.preview-avatar--empty i {
    font-size: 1.5rem;
}

.preview-name {
    font-size: 1rem;
    font-weight: 600;
    color: #0D1F1B;
    margin: 0.75rem 0 0.25rem;
}

.preview-tagline {
    font-size: 0.8125rem;
    color: #6b7280;
    margin: 0 0 0.75rem;
    line-height: 1.4;
}

.preview-social {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.social-link {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: white;
    color: #6b7280;
    font-size: 0.875rem;
    transition: all 0.15s ease;
}

.social-link:hover {
    background: #106B4F;
    color: white;
}

/* Sidebar Cards */
.sidebar-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    overflow: hidden;
}

.sidebar-card-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.875rem 1rem;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    font-size: 0.875rem;
    font-weight: 600;
    color: #374151;
}

.sidebar-card-content {
    padding: 1rem;
}

/* Completeness Card */
.completeness-bar {
    height: 8px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 0.75rem;
}

.completeness-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.3s ease;
}

.completeness-fill--low {
    background: #ef4444;
}

.completeness-fill--medium {
    background: #f59e0b;
}

.completeness-fill--full {
    background: #10b981;
}

.completeness-text {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.completeness-percent {
    font-size: 1.125rem;
    font-weight: 700;
    color: #0D1F1B;
}

.completeness-label {
    font-size: 0.75rem;
    color: #6b7280;
}

.completeness-missing {
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}

.missing-title {
    font-size: 0.75rem;
    font-weight: 500;
    color: #6b7280;
    margin: 0 0 0.5rem;
}

.missing-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 0.375rem;
}

.missing-list li {
    font-size: 0.75rem;
    color: #f59e0b;
    background: #fef3c7;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

/* Tips Card */
.tips-list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.tips-list li {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    font-size: 0.8125rem;
    color: #374151;
    line-height: 1.4;
}

.tip-icon {
    color: #10b981;
    font-size: 0.875rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

/* Media Stats Card */
.media-stats {
    display: flex;
    gap: 1.5rem;
}

.media-stat {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.media-stat-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: #106B4F;
}

.media-stat-label {
    font-size: 0.75rem;
    color: #6b7280;
}

/* Mobile Save Bar */
.mobile-save-bar {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background: white;
    border-top: 1px solid #e5e7eb;
    padding: 1rem;
    z-index: 100;
}

.mobile-save-btn {
    width: 100%;
}

/* Desktop styles */
@media (min-width: 768px) {
    .profile-info-grid {
        flex-direction: row;
        align-items: flex-start;
    }
}

@media (min-width: 1024px) {
    .sticky-header {
        margin: -1.5rem -1.5rem 2rem;
        padding: 1rem 2rem;
    }

    .cancel-btn {
        display: inline-flex;
    }

    .content-grid {
        grid-template-columns: 1fr 320px;
    }

    .sidebar-column {
        position: sticky;
        top: 100px;
        align-self: start;
    }

    .profile-edit-page {
        padding-bottom: 2rem;
    }
}

/* Mobile styles */
@media (max-width: 1023px) {
    .mobile-save-bar {
        display: block;
    }

    .header-actions .console-button:last-child {
        display: none;
    }
}

@media (max-width: 640px) {
    .sticky-header {
        margin: -1rem -1rem 1rem;
        padding: 0.875rem 1rem;
    }

    .header-title {
        font-size: 1rem;
    }

    .header-subtitle {
        display: none;
    }

    .media-stats {
        gap: 1rem;
    }
}
</style>
