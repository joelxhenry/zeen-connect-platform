<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import { ConsoleButton, ConsoleFormCard } from '@/components/console';
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import GalleryUpload from '@/components/media/GalleryUpload.vue';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import Checkbox from 'primevue/checkbox';
import DatePicker from 'primevue/datepicker';
import SelectButton from 'primevue/selectbutton';
import ConfirmDialog from 'primevue/confirmdialog';
import { useConfirm } from 'primevue/useconfirm';
import providerRoutes from '@/routes/provider';
import { resolveUrl } from '@/utils/url';

interface Category {
    id: number;
    uuid: string;
    name: string;
    slug: string;
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

interface Occurrence {
    id: number;
    uuid: string;
    start_datetime: string;
    end_datetime: string;
    date_display: string;
    time_display: string;
    datetime_display: string;
    spots_remaining: number;
    has_availability: boolean;
    is_full: boolean;
    status: string;
    status_label: string;
    is_scheduled: boolean;
    is_cancelled: boolean;
    is_upcoming: boolean;
    can_be_cancelled: boolean;
}

interface RecurrenceRule {
    frequency: string;
    frequency_label: string;
    interval: number;
    days_of_week: number[];
    day_names: string[];
    time_of_day: string;
    time_display: string;
    starts_at: string;
    ends_at: string | null;
    max_occurrences: number | null;
    description: string;
}

interface Event {
    id: number;
    uuid: string;
    name: string;
    description: string;
    event_type: string;
    location_type: string;
    location: string;
    virtual_meeting_url: string;
    duration_minutes: number;
    capacity: number | null;
    price: number;
    status: string;
    status_label: string;
    is_active: boolean;
    is_recurring: boolean;
    is_published: boolean;
    category_ids: number[];
    team_member_ids: number[];
    cover?: MediaItem | null;
    gallery?: MediaItem[];
    videos?: VideoEmbed[];
    booking_settings: {
        use_provider_defaults: boolean;
        requires_approval: boolean | null;
        deposit_type: string | null;
        deposit_amount: number | null;
        cancellation_policy: string | null;
        advance_booking_days: number | null;
        min_booking_notice_hours: number | null;
        allow_waitlist: boolean;
        max_spots_per_booking: number;
    };
    occurrences: Occurrence[];
    recurrence_rule: RecurrenceRule | null;
}

interface Props {
    event: Event;
    categories: Category[];
    providerDefaults: Record<string, unknown>;
    tierRestrictions: Record<string, unknown>;
    teamMembers: TeamMember[];
    providerSubdomain: string;
}

const props = defineProps<Props>();
const confirm = useConfirm();

const form = useForm({
    name: props.event.name,
    description: props.event.description || '',
    event_type: props.event.event_type as 'one_time' | 'recurring',
    location_type: props.event.location_type as 'in_person' | 'virtual',
    location: props.event.location || '',
    virtual_meeting_url: props.event.virtual_meeting_url || '',
    duration_minutes: props.event.duration_minutes,
    capacity: props.event.capacity,
    price: props.event.price,
    is_active: props.event.is_active,
    category_ids: props.event.category_ids || [],
    team_member_ids: props.event.team_member_ids || [],
    use_provider_defaults: props.event.booking_settings?.use_provider_defaults ?? true,
    requires_approval: props.event.booking_settings?.requires_approval,
    deposit_type: props.event.booking_settings?.deposit_type,
    deposit_amount: props.event.booking_settings?.deposit_amount,
    cancellation_policy: props.event.booking_settings?.cancellation_policy,
    advance_booking_days: props.event.booking_settings?.advance_booking_days,
    min_booking_notice_hours: props.event.booking_settings?.min_booking_notice_hours,
    allow_waitlist: props.event.booking_settings?.allow_waitlist ?? false,
    max_spots_per_booking: props.event.booking_settings?.max_spots_per_booking ?? 10,
    // One-time event - get from first occurrence
    occurrence_datetime: props.event.occurrences?.[0]?.start_datetime
        ? new Date(props.event.occurrences[0].start_datetime)
        : null,
    // Recurrence settings
    recurrence: {
        frequency: props.event.recurrence_rule?.frequency || 'weekly',
        interval: props.event.recurrence_rule?.interval || 1,
        days_of_week: props.event.recurrence_rule?.days_of_week || [],
        time_of_day: props.event.recurrence_rule?.time_of_day || '',
        starts_at: props.event.recurrence_rule?.starts_at
            ? new Date(props.event.recurrence_rule.starts_at)
            : null,
        ends_at: props.event.recurrence_rule?.ends_at
            ? new Date(props.event.recurrence_rule.ends_at)
            : null,
        max_occurrences: props.event.recurrence_rule?.max_occurrences,
    },
});

const showAdvancedSettings = ref(!props.event.booking_settings?.use_provider_defaults);
const unlimitedCapacity = ref(props.event.capacity === null);
const generatingOccurrences = ref(false);

// Media state
const coverImage = ref<MediaItem | null>(props.event.cover || null);
const galleryImages = ref<MediaItem[]>(props.event.gallery || []);
const eventVideos = ref<VideoEmbed[]>(props.event.videos || []);

// Media upload URLs
const coverUploadUrl = computed(() =>
    resolveUrl(providerRoutes.media.event.upload.url(props.event.uuid))
);

const galleryUploadUrl = computed(() =>
    resolveUrl(providerRoutes.media.event.uploadMultiple.url(props.event.uuid))
);

const videoAddUrl = computed(() =>
    resolveUrl(providerRoutes.videos.event.add.url(props.event.uuid))
);

const locationTypeOptions = [
    { label: 'In Person', value: 'in_person' },
    { label: 'Virtual', value: 'virtual' },
];

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

const weekDays = [
    { label: 'Sun', value: 0 },
    { label: 'Mon', value: 1 },
    { label: 'Tue', value: 2 },
    { label: 'Wed', value: 3 },
    { label: 'Thu', value: 4 },
    { label: 'Fri', value: 5 },
    { label: 'Sat', value: 6 },
];

const depositTypeOptions = [
    { label: 'No deposit', value: 'none' },
    { label: 'Percentage', value: 'percentage' },
];

const cancellationPolicyOptions = [
    { label: 'Flexible (24 hours)', value: 'flexible' },
    { label: 'Moderate (48 hours)', value: 'moderate' },
    { label: 'Strict (7 days)', value: 'strict' },
];

// Watch unlimited capacity toggle
watch(unlimitedCapacity, (val) => {
    if (val) {
        form.capacity = null;
    } else if (form.capacity === null) {
        form.capacity = 20;
    }
});

// Min date for date pickers
const minDate = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return today;
});

// Upcoming occurrences
const upcomingOccurrences = computed(() => {
    return (props.event.occurrences || []).filter(o => o.is_upcoming && o.is_scheduled);
});

const formatDate = (date: Date): string => {
    return date.toISOString().split('T')[0];
};

const submit = () => {
    const formData = { ...form.data() };

    if (form.event_type === 'recurring' && form.recurrence.time_of_day) {
        formData.recurrence = {
            ...form.recurrence,
            starts_at: form.recurrence.starts_at ? formatDate(form.recurrence.starts_at as Date) : null,
            ends_at: form.recurrence.ends_at ? formatDate(form.recurrence.ends_at as Date) : null,
        };
    }

    if (form.event_type === 'one_time' && form.occurrence_datetime) {
        formData.occurrence_datetime = (form.occurrence_datetime as Date).toISOString();
    }

    form.transform(() => formData).put(resolveUrl(providerRoutes.events.update.url(props.event.uuid)), {
        preserveScroll: true,
    });
};

const cancel = () => {
    router.get(resolveUrl(providerRoutes.events.index.url()));
};

const publishEvent = () => {
    router.post(resolveUrl(providerRoutes.events.publish.url(props.event.uuid)), {}, {
        preserveScroll: true,
    });
};

const cancelEvent = () => {
    confirm.require({
        message: 'Are you sure you want to cancel this event? This will cancel all upcoming occurrences.',
        header: 'Cancel Event',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Keep Event',
        acceptLabel: 'Cancel Event',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(resolveUrl(providerRoutes.events.cancel.url(props.event.uuid)), {}, {
                preserveScroll: true,
            });
        },
    });
};

const generateOccurrences = () => {
    generatingOccurrences.value = true;
    router.post(
        resolveUrl(providerRoutes.events.occurrences.generate.url(props.event.uuid)),
        { months: 3 },
        {
            preserveScroll: true,
            onFinish: () => {
                generatingOccurrences.value = false;
            },
        }
    );
};

const cancelOccurrence = (occurrence: Occurrence) => {
    confirm.require({
        message: `Cancel the occurrence on ${occurrence.datetime_display}? Registered attendees will be notified.`,
        header: 'Cancel Occurrence',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Keep',
        acceptLabel: 'Cancel',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.post(resolveUrl(providerRoutes.occurrences.cancel.url(occurrence.uuid)), {}, {
                preserveScroll: true,
            });
        },
    });
};

const toggleDayOfWeek = (day: number) => {
    const index = form.recurrence.days_of_week.indexOf(day);
    if (index === -1) {
        form.recurrence.days_of_week.push(day);
    } else {
        form.recurrence.days_of_week.splice(index, 1);
    }
    form.recurrence.days_of_week.sort((a, b) => a - b);
};
</script>

<template>
    <ConsoleLayout title="Edit Event">
        <ConfirmDialog />

        <div class="edit-event-page">
            <!-- Header -->
            <div class="page-header">
                <button class="back-btn" @click="cancel">
                    <i class="pi pi-arrow-left"></i>
                </button>
                <div class="header-info">
                    <div class="header-title-row">
                        <h1 class="page-title">{{ event.name }}</h1>
                        <span class="event-status" :class="`status-${event.status}`">
                            {{ event.status_label }}
                        </span>
                    </div>
                    <p class="page-subtitle">
                        {{ event.is_recurring ? 'Recurring' : 'One-time' }} event
                    </p>
                </div>
                <div class="header-actions">
                    <ConsoleButton
                        v-if="event.status === 'draft'"
                        label="Publish"
                        icon="pi pi-check-circle"
                        variant="primary"
                        size="small"
                        @click="publishEvent"
                    />
                    <ConsoleButton
                        v-else-if="event.status === 'published'"
                        label="Cancel Event"
                        icon="pi pi-times-circle"
                        variant="danger"
                        size="small"
                        @click="cancelEvent"
                    />
                </div>
            </div>

            <!-- Error Summary -->
            <div v-if="Object.keys(form.errors).length > 0" class="error-summary">
                <div class="error-summary-header">
                    <i class="pi pi-exclamation-circle"></i>
                    <span>Please fix the following errors:</span>
                </div>
                <ul class="error-list">
                    <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                </ul>
            </div>

            <div class="page-layout">
                <form @submit.prevent="submit" class="event-form">
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
                                Recommended size: 1200x600px. This image will be displayed on your event page.
                            </p>
                        </div>
                    </ConsoleFormCard>

                    <!-- Gallery & Videos -->
                    <ConsoleFormCard title="Gallery & Videos">
                        <div class="media-section">
                            <GalleryUpload
                                v-model="galleryImages"
                                v-model:videos="eventVideos"
                                :upload-url="galleryUploadUrl"
                                :video-add-url="videoAddUrl"
                                collection="gallery"
                                :max-files="6"
                                :max-videos="3"
                                :show-videos="true"
                            />
                            <p class="media-hint">
                                Add images and videos to showcase your event. Maximum 6 images and 3 videos (YouTube or Vimeo).
                            </p>
                        </div>
                    </ConsoleFormCard>

                    <!-- Basic Info -->
                    <ConsoleFormCard title="Basic Information">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label for="name" class="form-label">Event Name *</label>
                                <InputText
                                    id="name"
                                    v-model="form.name"
                                    placeholder="e.g., Yoga Class, Workshop, Seminar"
                                    class="form-input"
                                    :class="{ 'p-invalid': form.errors.name }"
                                />
                                <small v-if="form.errors.name" class="p-error">{{ form.errors.name }}</small>
                            </div>

                            <div class="form-field full-width">
                                <label for="description" class="form-label">Description</label>
                                <Textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Describe your event..."
                                    class="form-input"
                                />
                            </div>

                            <div class="form-field">
                                <label for="categories" class="form-label">Categories</label>
                                <MultiSelect
                                    id="categories"
                                    v-model="form.category_ids"
                                    :options="categories"
                                    optionLabel="name"
                                    optionValue="id"
                                    placeholder="Select categories"
                                    display="chip"
                                    class="form-input"
                                />
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Schedule (One-time) -->
                    <ConsoleFormCard v-if="event.event_type === 'one_time'" title="Schedule">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label for="occurrence_datetime" class="form-label">Event Date & Time *</label>
                                <DatePicker
                                    id="occurrence_datetime"
                                    v-model="form.occurrence_datetime"
                                    showTime
                                    hourFormat="12"
                                    :minDate="minDate"
                                    placeholder="Select date and time"
                                    class="form-input"
                                    :class="{ 'p-invalid': form.errors.occurrence_datetime }"
                                />
                                <small v-if="form.errors.occurrence_datetime" class="p-error">{{ form.errors.occurrence_datetime }}</small>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Recurrence Pattern -->
                    <ConsoleFormCard v-if="event.is_recurring" title="Recurrence Pattern">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label class="form-label">Days of Week *</label>
                                <div class="days-of-week">
                                    <button
                                        v-for="day in weekDays"
                                        :key="day.value"
                                        type="button"
                                        class="day-btn"
                                        :class="{ active: form.recurrence.days_of_week.includes(day.value) }"
                                        @click="toggleDayOfWeek(day.value)"
                                    >
                                        {{ day.label }}
                                    </button>
                                </div>
                            </div>

                            <div class="form-field">
                                <label for="time_of_day" class="form-label">Time *</label>
                                <InputText
                                    id="time_of_day"
                                    v-model="form.recurrence.time_of_day"
                                    type="time"
                                    class="form-input"
                                />
                            </div>

                            <div class="form-field">
                                <label for="interval" class="form-label">Repeat Every</label>
                                <Select
                                    id="interval"
                                    v-model="form.recurrence.interval"
                                    :options="[
                                        { label: 'Every week', value: 1 },
                                        { label: 'Every 2 weeks', value: 2 },
                                        { label: 'Every 3 weeks', value: 3 },
                                        { label: 'Every 4 weeks', value: 4 },
                                    ]"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="form-input"
                                />
                            </div>

                            <div class="form-field">
                                <label for="ends_at" class="form-label">End Date</label>
                                <DatePicker
                                    id="ends_at"
                                    v-model="form.recurrence.ends_at"
                                    :minDate="minDate"
                                    placeholder="Optional end date"
                                    class="form-input"
                                />
                                <small class="form-hint">Leave empty for ongoing events</small>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Upcoming Occurrences -->
                    <ConsoleFormCard v-if="event.is_recurring" title="Upcoming Occurrences">
                        <template #header>
                            <div class="occurrences-header">
                                <div>
                                    <h3>Upcoming Occurrences</h3>
                                    <p class="header-subtitle">{{ upcomingOccurrences.length }} scheduled</p>
                                </div>
                                <ConsoleButton
                                    label="Generate More"
                                    icon="pi pi-plus"
                                    variant="secondary"
                                    size="small"
                                    :loading="generatingOccurrences"
                                    @click="generateOccurrences"
                                />
                            </div>
                        </template>

                        <div v-if="upcomingOccurrences.length === 0" class="empty-occurrences">
                            <i class="pi pi-calendar-times"></i>
                            <p>No upcoming occurrences scheduled</p>
                            <ConsoleButton
                                label="Generate Occurrences"
                                icon="pi pi-plus"
                                variant="primary"
                                size="small"
                                :loading="generatingOccurrences"
                                @click="generateOccurrences"
                            />
                        </div>

                        <div v-else class="occurrences-list">
                            <div
                                v-for="occurrence in upcomingOccurrences"
                                :key="occurrence.uuid"
                                class="occurrence-item"
                            >
                                <div class="occurrence-info">
                                    <span class="occurrence-date">{{ occurrence.date_display }}</span>
                                    <span class="occurrence-time">{{ occurrence.time_display }}</span>
                                </div>
                                <div class="occurrence-stats">
                                    <span class="occurrence-capacity" :class="{ full: occurrence.is_full }">
                                        <i class="pi pi-users"></i>
                                        {{ occurrence.spots_remaining }} spots left
                                    </span>
                                </div>
                                <button
                                    v-if="occurrence.can_be_cancelled"
                                    type="button"
                                    class="occurrence-cancel-btn"
                                    @click="cancelOccurrence(occurrence)"
                                    title="Cancel this occurrence"
                                >
                                    <i class="pi pi-times"></i>
                                </button>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Location -->
                    <ConsoleFormCard title="Location">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label class="form-label">Location Type *</label>
                                <SelectButton
                                    v-model="form.location_type"
                                    :options="locationTypeOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="location-type-selector"
                                />
                            </div>

                            <div v-if="form.location_type === 'in_person'" class="form-field full-width">
                                <label for="location" class="form-label">Address *</label>
                                <InputText
                                    id="location"
                                    v-model="form.location"
                                    placeholder="Enter the event location"
                                    class="form-input"
                                    :class="{ 'p-invalid': form.errors.location }"
                                />
                                <small v-if="form.errors.location" class="p-error">{{ form.errors.location }}</small>
                            </div>

                            <div v-if="form.location_type === 'virtual'" class="form-field full-width">
                                <label for="virtual_meeting_url" class="form-label">Meeting URL *</label>
                                <InputText
                                    id="virtual_meeting_url"
                                    v-model="form.virtual_meeting_url"
                                    placeholder="https://zoom.us/j/..."
                                    class="form-input"
                                    :class="{ 'p-invalid': form.errors.virtual_meeting_url }"
                                />
                                <small v-if="form.errors.virtual_meeting_url" class="p-error">{{ form.errors.virtual_meeting_url }}</small>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Pricing & Capacity -->
                    <ConsoleFormCard title="Pricing & Capacity">
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
                                <small v-if="form.errors.price" class="p-error">{{ form.errors.price }}</small>
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
                                />
                            </div>

                            <div class="form-field">
                                <label class="form-label">Capacity</label>
                                <div class="capacity-field">
                                    <div class="switch-field">
                                        <ToggleSwitch v-model="unlimitedCapacity" />
                                        <span class="switch-label">Unlimited</span>
                                    </div>
                                    <InputNumber
                                        v-if="!unlimitedCapacity"
                                        v-model="form.capacity"
                                        :min="1"
                                        :max="10000"
                                        placeholder="Max attendees"
                                        class="form-input capacity-input"
                                    />
                                </div>
                            </div>

                            <div class="form-field">
                                <label class="form-label">Status</label>
                                <div class="switch-field">
                                    <ToggleSwitch v-model="form.is_active" />
                                    <span class="switch-label">{{ form.is_active ? 'Active' : 'Inactive' }}</span>
                                </div>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Booking Settings -->
                    <ConsoleFormCard>
                        <template #header>
                            <div class="advanced-header">
                                <div class="advanced-title">
                                    <h3>Booking Settings</h3>
                                    <p class="advanced-subtitle">Override your default booking settings</p>
                                </div>
                                <ToggleSwitch
                                    v-model="showAdvancedSettings"
                                    @update:modelValue="(val: boolean) => { form.use_provider_defaults = !val; }"
                                />
                            </div>
                        </template>

                        <div v-if="showAdvancedSettings" class="form-grid">
                            <div class="form-field">
                                <label class="form-label">Requires Approval</label>
                                <div class="switch-field">
                                    <ToggleSwitch v-model="form.requires_approval" />
                                    <span class="switch-label">
                                        {{ form.requires_approval ? 'Manual approval' : 'Auto-confirm' }}
                                    </span>
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

                            <div v-if="form.deposit_type === 'percentage'" class="form-field">
                                <label for="deposit_amount" class="form-label">Deposit %</label>
                                <InputNumber
                                    id="deposit_amount"
                                    v-model="form.deposit_amount"
                                    suffix="%"
                                    :min="0"
                                    :max="100"
                                    class="form-input"
                                />
                            </div>

                            <div class="form-field">
                                <label for="cancellation_policy" class="form-label">Cancellation Policy</label>
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
                                <label class="form-label">Allow Waitlist</label>
                                <div class="switch-field">
                                    <ToggleSwitch v-model="form.allow_waitlist" />
                                    <span class="switch-label">
                                        {{ form.allow_waitlist ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </div>
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
                <aside v-if="teamMembers.length > 0" class="team-sidebar">
                    <div class="sidebar-card">
                        <h3 class="sidebar-title">Team Assignment</h3>
                        <p class="sidebar-description">Select who can host this event</p>
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
                                    <img v-if="member.avatar" :src="member.avatar" :alt="member.name" />
                                    <span v-else class="avatar-placeholder">
                                        {{ member.name.charAt(0).toUpperCase() }}
                                    </span>
                                </div>
                                <span class="member-name">{{ member.name }}</span>
                            </label>
                        </div>
                    </div>
                </aside>
            </div>

            <!-- Floating Action Bar -->
            <div class="floating-actions">
                <div class="floating-actions-content">
                    <div class="floating-buttons">
                        <ConsoleButton
                            type="button"
                            label="Cancel"
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
        </div>
    </ConsoleLayout>
</template>

<style scoped>
.edit-event-page {
    max-width: 960px;
    margin: 0 auto;
    padding-bottom: 2rem;
}

/* Error Summary */
.error-summary {
    background-color: #fef2f2;
    border: 1px solid #fecaca;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}

.error-summary-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-weight: 600;
    color: #dc2626;
    margin-bottom: 0.5rem;
}

.error-summary-header i {
    font-size: 1.125rem;
}

.error-list {
    margin: 0;
    padding-left: 1.5rem;
    color: #b91c1c;
    font-size: 0.875rem;
    line-height: 1.6;
}

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
    min-width: 0;
}

.header-title-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.page-title {
    margin: 0;
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.event-status {
    padding: 0.25rem 0.625rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.event-status.status-published {
    background-color: #d1fae5;
    color: #065f46;
}

.event-status.status-draft {
    background-color: #f1f5f9;
    color: #475569;
}

.event-status.status-cancelled {
    background-color: #fee2e2;
    color: #991b1b;
}

.page-subtitle {
    margin: 0.25rem 0 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.header-actions {
    flex-shrink: 0;
}

/* Form styles (same as Create.vue) */
.event-form {
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

/* Days of Week */
.days-of-week {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.day-btn {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 50%;
    background-color: white;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.15s ease;
}

.day-btn:hover {
    background-color: var(--color-slate-50, #f8fafc);
}

.day-btn.active {
    background-color: #106b4f;
    border-color: #106b4f;
    color: white;
}

/* Occurrences */
.occurrences-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
}

.occurrences-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.header-subtitle {
    margin: 0.125rem 0 0;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
}

.empty-occurrences {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 2rem;
    text-align: center;
}

.empty-occurrences i {
    font-size: 2rem;
    color: var(--color-slate-300, #cbd5e1);
}

.empty-occurrences p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.occurrences-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
}

.occurrence-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background-color: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.occurrence-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.occurrence-date {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-900, #0f172a);
}

.occurrence-time {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.occurrence-stats {
    display: flex;
    align-items: center;
}

.occurrence-capacity {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--color-slate-600, #475569);
}

.occurrence-capacity.full {
    color: #ef4444;
}

.occurrence-cancel-btn {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: 1px solid var(--color-slate-200, #e2e8f0);
    border-radius: 0.375rem;
    color: var(--color-slate-400, #94a3b8);
    cursor: pointer;
    transition: all 0.15s ease;
}

.occurrence-cancel-btn:hover {
    border-color: #ef4444;
    color: #ef4444;
}

/* Capacity Field */
.capacity-field {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.capacity-input {
    margin-top: 0.25rem;
}

/* Location Type Selector */
.location-type-selector :deep(.p-selectbutton) {
    display: flex;
    gap: 0.5rem;
}

.location-type-selector :deep(.p-button) {
    flex: 1;
    justify-content: center;
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
    justify-content: flex-end;
}

.floating-buttons {
    display: flex;
    gap: 0.75rem;
}

@media (min-width: 1024px) {
    .floating-actions {
        left: 280px;
    }
}

/* Team Sidebar (same as Create.vue) */
.team-sidebar {
    position: sticky;
    top: 5rem;
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
}

@media (max-width: 767px) {
    .team-sidebar {
        order: -1;
        position: relative;
        top: 0;
    }

    .page-header {
        flex-wrap: wrap;
    }

    .header-actions {
        width: 100%;
        margin-top: 0.5rem;
    }

    .header-actions :deep(button) {
        width: 100%;
    }
}
</style>
