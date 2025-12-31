<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import { ConsoleButton, ConsoleFormCard } from '@/components/console';
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import GalleryUpload from '@/components/media/GalleryUpload.vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import Checkbox from 'primevue/checkbox';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import providerRoutes from '@/routes/provider';
import providersiteRoutes from '@/routes/providersite';
import { resolveUrl } from '@/utils/url';

interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
    icon?: string;
}

interface TeamMember {
    id: number;
    name: string;
    avatar?: string;
}

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

interface Service {
    id: number;
    uuid: string;
    name: string;
    description: string;
    duration_minutes: number;
    duration_display: string;
    price: number;
    price_display: string;
    is_active: boolean;
    sort_order: number;
    category: Category | null;
    cover_url?: string;
    cover_thumbnail?: string;
    cover?: MediaItem | null;
    gallery?: MediaItem[];
    videos?: VideoEmbed[];
    team_member_ids?: number[];
    booking_settings?: {
        use_provider_defaults: boolean;
        requires_approval: boolean | null;
        deposit_type: string | null;
        deposit_amount: number | null;
        cancellation_policy: string | null;
        advance_booking_days: number | null;
        min_booking_notice_hours: number | null;
    };
}

interface Props {
    service: Service;
    categories: Category[];
    providerDefaults: {
        requires_approval: boolean;
        deposit_type: string;
        deposit_amount: number;
        cancellation_policy: string;
        advance_booking_days: number;
        min_booking_notice_hours: number;
    };
    tierRestrictions: Record<string, unknown>;
    providerSubdomain: string;
    teamMembers: TeamMember[];
}

const props = defineProps<Props>();
const confirm = useConfirm();

const form = useForm({
    name: props.service.name,
    description: props.service.description || '',
    category_id: props.service.category?.id ?? null,
    duration_minutes: props.service.duration_minutes,
    price: props.service.price,
    is_active: props.service.is_active,
    team_member_ids: props.service.team_member_ids || [],
    use_provider_defaults: props.service.booking_settings?.use_provider_defaults ?? true,
    requires_approval: props.service.booking_settings?.requires_approval ?? undefined,
    deposit_type: props.service.booking_settings?.deposit_type ?? null,
    deposit_amount: props.service.booking_settings?.deposit_amount ?? null,
    cancellation_policy: props.service.booking_settings?.cancellation_policy ?? null,
    advance_booking_days: props.service.booking_settings?.advance_booking_days ?? null,
    min_booking_notice_hours: props.service.booking_settings?.min_booking_notice_hours ?? null,
});

// Track initial form values for dirty detection
const initialValues = ref<string>('');
const isDirty = ref(false);

onMounted(() => {
    initialValues.value = JSON.stringify(form.data());
});

// Watch for form changes
watch(
    () => form.data(),
    (newValues) => {
        isDirty.value = JSON.stringify(newValues) !== initialValues.value;
    },
    { deep: true }
);

const showAdvancedSettings = ref(!form.use_provider_defaults);

// Cover image state
const coverImage = ref<MediaItem | null>(props.service.cover || null);

// Gallery images state
const galleryImages = ref<MediaItem[]>(props.service.gallery || []);

// Video embeds state
const serviceVideos = ref<VideoEmbed[]>(props.service.videos || []);

const durationOptions = [
    { label: '15 minutes', value: 15 },
    { label: '30 minutes', value: 30 },
    { label: '45 minutes', value: 45 },
    { label: '1 hour', value: 60 },
    { label: '1.5 hours', value: 90 },
    { label: '2 hours', value: 120 },
    { label: '2.5 hours', value: 150 },
    { label: '3 hours', value: 180 },
    { label: '4 hours', value: 240 },
    { label: '5 hours', value: 300 },
    { label: '6 hours', value: 360 },
    { label: '8 hours', value: 480 },
];

const depositTypeOptions = [
    { label: 'No deposit', value: 'none' },
    { label: 'Fixed amount', value: 'fixed' },
    { label: 'Percentage', value: 'percentage' },
    { label: 'Full payment', value: 'full' },
];

const cancellationPolicyOptions = [
    { label: 'Flexible (24 hours)', value: 'flexible' },
    { label: 'Moderate (48 hours)', value: 'moderate' },
    { label: 'Strict (7 days)', value: 'strict' },
    { label: 'Non-refundable', value: 'non_refundable' },
];

const categoryOptions = computed(() => [
    { id: null, name: 'No category' },
    ...props.categories,
]);

// Media upload URLs
const coverUploadUrl = computed(() =>
    resolveUrl(providerRoutes.media.service.upload.url(props.service.uuid))
);

const galleryUploadUrl = computed(() =>
    resolveUrl(providerRoutes.media.service.uploadMultiple.url(props.service.uuid))
);

const videoAddUrl = computed(() =>
    resolveUrl(providerRoutes.videos.service.add.url(props.service.uuid))
);

// Live booking URL
const liveBookingUrl = computed(() => {
    const baseUrl = resolveUrl(providersiteRoutes.book.url(props.providerSubdomain));
    return `${baseUrl}?service=${props.service.id}`;
});

watch(showAdvancedSettings, (val) => {
    form.use_provider_defaults = !val;
});

const submit = () => {
    form.put(resolveUrl(providerRoutes.services.update.url(props.service.uuid)), {
        preserveScroll: true,
        onSuccess: () => {
            // Reset dirty state after successful save
            initialValues.value = JSON.stringify(form.data());
            isDirty.value = false;
        },
    });
};

const cancel = () => {
    router.get(resolveUrl(providerRoutes.services.index.url()));
};

const deleteService = () => {
    confirm.require({
        message: `Are you sure you want to delete "${props.service.name}"? This action cannot be undone.`,
        header: 'Delete Service',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Delete',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(resolveUrl(providerRoutes.services.destroy.url(props.service.uuid)));
        },
    });
};

const openLiveBooking = () => {
    window.open(liveBookingUrl.value, '_blank');
};
</script>

<template>
    <ConsoleLayout title="Edit Service">
        <ConfirmDialog />

        <div class="edit-service-page">
            <!-- Header -->
            <div class="page-header">
                <button class="back-btn" @click="cancel">
                    <i class="pi pi-arrow-left"></i>
                </button>
                <div class="header-info">
                    <h1 class="page-title">Edit Service</h1>
                    <p class="page-subtitle">{{ service.name }}</p>
                </div>
                <div class="header-actions">
                    <button
                        class="action-btn live-btn"
                        @click="openLiveBooking"
                        title="View live booking page"
                    >
                        <i class="pi pi-external-link"></i>
                    </button>
                    <button class="action-btn delete-btn" @click="deleteService" title="Delete service">
                        <i class="pi pi-trash"></i>
                    </button>
                </div>
            </div>

            <div class="page-layout">
                <form @submit.prevent="submit" class="service-form">
                    <!-- Basic Info -->
                    <ConsoleFormCard title="Basic Information">
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="name" class="form-label">Service Name *</label>
                            <InputText
                                id="name"
                                v-model="form.name"
                                placeholder="e.g., Haircut, Massage, Consultation"
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.name }"
                            />
                            <small v-if="form.errors.name" class="p-error">{{
                                form.errors.name
                            }}</small>
                        </div>

                        <div class="form-field">
                            <label for="category" class="form-label">Category</label>
                            <Select
                                id="category"
                                v-model="form.category_id"
                                :options="categoryOptions"
                                optionLabel="name"
                                optionValue="id"
                                placeholder="Select a category"
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.category_id }"
                            />
                            <small v-if="form.errors.category_id" class="p-error">{{
                                form.errors.category_id
                            }}</small>
                        </div>

                        <div class="form-field full-width">
                            <label for="description" class="form-label">Description</label>
                            <Textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                placeholder="Describe your service..."
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.description }"
                            />
                            <small v-if="form.errors.description" class="p-error">{{
                                form.errors.description
                            }}</small>
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Pricing & Duration -->
                <ConsoleFormCard title="Pricing & Duration">
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="price" class="form-label">Price *</label>
                            <InputNumber
                                id="price"
                                v-model="form.price"
                                mode="currency"
                                currency="USD"
                                locale="en-US"
                                :min="0"
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.price }"
                            />
                            <small v-if="form.errors.price" class="p-error">{{
                                form.errors.price
                            }}</small>
                        </div>

                        <div class="form-field">
                            <label for="duration" class="form-label">Duration *</label>
                            <Select
                                id="duration"
                                v-model="form.duration_minutes"
                                :options="durationOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="form-input"
                                :class="{ 'p-invalid': form.errors.duration_minutes }"
                            />
                            <small v-if="form.errors.duration_minutes" class="p-error">{{
                                form.errors.duration_minutes
                            }}</small>
                        </div>

                        <div class="form-field">
                            <label class="form-label">Active</label>
                            <div class="switch-field">
                                <ToggleSwitch v-model="form.is_active" />
                                <span class="switch-label">{{
                                    form.is_active ? 'Service is visible' : 'Service is hidden'
                                }}</span>
                            </div>
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Cover Image -->
                <ConsoleFormCard title="Cover Image">
                    <div class="media-section">
                        <SingleImageUpload
                            v-model="coverImage"
                            :upload-url="coverUploadUrl"
                            collection="cover"
                            shape="cover"
                            placeholder="Upload cover image"
                        />
                        <p class="media-hint">
                            Recommended size: 1200x600px. This image will be displayed on your service page.
                        </p>
                    </div>
                </ConsoleFormCard>

                <!-- Gallery & Videos -->
                <ConsoleFormCard title="Gallery & Videos">
                    <div class="media-section">
                        <GalleryUpload
                            v-model="galleryImages"
                            v-model:videos="serviceVideos"
                            :upload-url="galleryUploadUrl"
                            :video-add-url="videoAddUrl"
                            collection="gallery"
                            :max-files="6"
                            :max-videos="3"
                            :show-videos="true"
                        />
                        <p class="media-hint">
                            Add images and videos to showcase your service. Maximum 6 images and 3 videos (YouTube or Vimeo).
                        </p>
                    </div>
                </ConsoleFormCard>

                <!-- Advanced Settings -->
                <ConsoleFormCard>
                    <template #header>
                        <div class="advanced-header">
                            <div class="advanced-title">
                                <h3>Booking Settings</h3>
                                <p class="advanced-subtitle">
                                    Override your default booking settings for this service
                                </p>
                            </div>
                            <ToggleSwitch v-model="showAdvancedSettings" />
                        </div>
                    </template>

                    <div v-if="showAdvancedSettings" class="form-grid">
                        <div class="form-field">
                            <label class="form-label">Requires Approval</label>
                            <div class="switch-field">
                                <ToggleSwitch v-model="form.requires_approval" />
                                <span class="switch-label">{{
                                    form.requires_approval
                                        ? 'Manual approval required'
                                        : 'Auto-confirm bookings'
                                }}</span>
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="deposit_type" class="form-label">Deposit Type</label>
                            <Select
                                id="deposit_type"
                                v-model="form.deposit_type"
                                :options="depositTypeOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="form-input"
                            />
                        </div>

                        <div
                            v-if="form.deposit_type === 'fixed' || form.deposit_type === 'percentage'"
                            class="form-field"
                        >
                            <label for="deposit_amount" class="form-label">
                                {{ form.deposit_type === 'percentage' ? 'Deposit %' : 'Deposit Amount' }}
                            </label>
                            <InputNumber
                                id="deposit_amount"
                                v-model="form.deposit_amount"
                                :mode="form.deposit_type === 'percentage' ? 'decimal' : 'currency'"
                                :currency="form.deposit_type === 'fixed' ? 'USD' : undefined"
                                :locale="form.deposit_type === 'fixed' ? 'en-US' : undefined"
                                :suffix="form.deposit_type === 'percentage' ? '%' : undefined"
                                :min="0"
                                :max="form.deposit_type === 'percentage' ? 100 : undefined"
                                class="form-input"
                            />
                        </div>

                        <div class="form-field">
                            <label for="cancellation_policy" class="form-label"
                                >Cancellation Policy</label
                            >
                            <Select
                                id="cancellation_policy"
                                v-model="form.cancellation_policy"
                                :options="cancellationPolicyOptions"
                                optionLabel="label"
                                optionValue="value"
                                class="form-input"
                            />
                        </div>

                        <div class="form-field">
                            <label for="advance_booking_days" class="form-label"
                                >Advance Booking (days)</label
                            >
                            <InputNumber
                                id="advance_booking_days"
                                v-model="form.advance_booking_days"
                                :min="1"
                                :max="365"
                                class="form-input"
                            />
                            <small class="form-hint">How far in advance can clients book?</small>
                        </div>

                        <div class="form-field">
                            <label for="min_booking_notice_hours" class="form-label"
                                >Minimum Notice (hours)</label
                            >
                            <InputNumber
                                id="min_booking_notice_hours"
                                v-model="form.min_booking_notice_hours"
                                :min="0"
                                :max="168"
                                class="form-input"
                            />
                            <small class="form-hint">Minimum hours before appointment</small>
                        </div>
                    </div>

                    <div v-else class="defaults-info">
                        <i class="pi pi-info-circle"></i>
                        <span>Using your default booking settings</span>
                    </div>
                </ConsoleFormCard>

                    <!-- Spacer for floating footer -->
                    <div class="form-footer-spacer"></div>
                </form>

                <!-- Team Member Sidebar -->
                <aside  v-if="teamMembers.length > 0"  class="team-sidebar">
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Team Assignment</h3>
                        <p class="sidebar-description">
                            Select who can perform this service
                        </p>
                        <div class="team-member-list">
                            <label
                                v-for="member in teamMembers"
                                :key="member.id"
                                class="team-member-item"
                                :class="{ selected: form.team_member_ids.includes(member.id) }"
                            >
                                <Checkbox
                                    v-model="form.team_member_ids"
                                    :value="member.id"
                                    :inputId="`team-member-${member.id}`"
                                />
                                <div class="member-avatar">
                                    <img
                                        v-if="member.avatar"
                                        :src="member.avatar"
                                        :alt="member.name"
                                    />
                                    <span v-else class="avatar-placeholder">
                                        {{ member.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <span class="member-name">{{ member.name }}</span>
                            </label>
                        </div>
                        <p v-if="form.team_member_ids.length === 0" class="sidebar-hint">
                            <i class="pi pi-info-circle"></i>
                            If none selected, assigned to you
                        </p>
                    </div>
                </aside>
            </div>

            <!-- Floating Action Bar -->
            <Transition name="slide-up">
                <div v-if="isDirty || form.processing" class="floating-actions">
                    <div class="floating-actions-content">
                        <span class="unsaved-text">
                            <i class="pi pi-exclamation-circle"></i>
                            Unsaved changes
                        </span>
                        <div class="floating-buttons">
                            <ConsoleButton
                                type="button"
                                label="Discard"
                                variant="secondary"
                                size="small"
                                @click="cancel"
                            />
                            <ConsoleButton
                                type="button"
                                label="Save Changes"
                                icon="pi pi-check"
                                variant="primary"
                                size="small"
                                :loading="form.processing"
                                @click="submit"
                            />
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.edit-service-page {
    max-width: 960px;
    margin: 0 auto;
    padding-bottom: 2rem;
}

/* Two-column layout */
.page-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}

@media (min-width: 768px) {
    .page-layout {
        grid-template-columns: 1fr 220px;
    }
}

/* Header */
.page-header {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.back-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.back-btn:hover {
    background-color: var(--color-slate-50, #f8fafc);
    color: var(--color-slate-900, #0f172a);
}

.header-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.page-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.page-subtitle {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.header-actions {
    display: flex;
    gap: 0.5rem;
}

.action-btn {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.5rem;
    color: var(--color-slate-500, #64748b);
    cursor: pointer;
    transition: all 0.15s ease;
}

.live-btn:hover {
    border-color: #4f46e5;
    color: #4f46e5;
    background-color: #eef2ff;
}

.delete-btn:hover {
    border-color: #ef4444;
    color: #ef4444;
    background-color: #fef2f2;
}

/* Form */
.service-form {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1rem;
}

@media (max-width: 639px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-field.full-width {
    grid-column: 1 / -1;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.form-input {
    width: 100%;
}

.form-hint {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.switch-field {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
}

.switch-label {
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

/* Media Section */
.media-section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.media-hint {
    margin: 0;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
}

/* Advanced Settings */
.advanced-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.advanced-title h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.advanced-subtitle {
    margin: 0.25rem 0 0;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
}

.defaults-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background-color: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.defaults-info i {
    color: #106b4f;
}

/* Footer Spacer */
.form-footer-spacer {
    height: 80px;
}

/* Floating Actions */
.floating-actions {
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 100;
    background: white;
    border-top: 1px solid var(--color-slate-200, #e2e8f0);
    box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
}

.floating-actions-content {
    max-width: 960px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.unsaved-text {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.unsaved-text i {
    color: #f59e0b;
}

.floating-buttons {
    display: flex;
    gap: 0.75rem;
}

/* Slide up animation */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: all 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}

@media (max-width: 639px) {
    .floating-actions-content {
        flex-direction: column;
        gap: 0.75rem;
        padding: 1rem;
    }

    .floating-buttons {
        width: 100%;
    }

    .floating-buttons :deep(button) {
        flex: 1;
    }
}

/* Sidebar offset for floating bar */
@media (min-width: 1024px) {
    .floating-actions {
        left: 280px;
    }
}

/* Team Sidebar */
.team-sidebar {
    position: sticky;
    top: 5rem; /* Account for topbar height */
    height: fit-content;
}

.sidebar-card {
    background: white;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.75rem;
    padding: 1rem;
}

.sidebar-title {
    margin: 0 0 0.25rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.sidebar-description {
    margin: 0 0 1rem;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
}

.team-member-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.team-member-item {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    padding: 0.5rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid transparent;
}

.team-member-item:hover {
    background-color: var(--color-slate-50, #f8fafc);
}

.team-member-item.selected {
    background-color: #eef2ff;
    border-color: #c7d2fe;
}

.member-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    overflow: hidden;
    flex-shrink: 0;
    background-color: var(--color-slate-100, #f1f5f9);
}

.member-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.avatar-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-slate-600, #475569);
    background-color: var(--color-slate-200, #e2e8f0);
}

.member-name {
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.sidebar-hint {
    display: flex;
    align-items: flex-start;
    gap: 0.375rem;
    margin: 0.75rem 0 0;
    padding: 0.5rem;
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
    background-color: var(--color-slate-50, #f8fafc);
    border-radius: 0.375rem;
}

.sidebar-hint i {
    font-size: 0.75rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
}

@media (max-width: 767px) {
    .team-sidebar {
        order: -1;
        position: relative;
        top: 0;
    }

    .sidebar-card {
        padding: 0.875rem;
    }

    .sidebar-title {
        font-size: 0.875rem;
    }

    .sidebar-description {
        margin-bottom: 0.75rem;
        font-size: 0.75rem;
    }

    .team-member-list {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .team-member-item {
        flex: 0 0 auto;
        padding: 0.375rem 0.625rem;
        gap: 0.5rem;
        background-color: var(--color-slate-50, #f8fafc);
        border: 1px solid var(--color-slate-200, #e2e8f0);
    }

    .team-member-item.selected {
        background-color: #eef2ff;
        border-color: #818cf8;
    }

    .member-avatar {
        width: 24px;
        height: 24px;
    }

    .avatar-placeholder {
        font-size: 0.625rem;
    }

    .member-name {
        font-size: 0.75rem;
    }

    .sidebar-hint {
        margin-top: 0.5rem;
        padding: 0.375rem 0.5rem;
        font-size: 0.6875rem;
    }
}
</style>
