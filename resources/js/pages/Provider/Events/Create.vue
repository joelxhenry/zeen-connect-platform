<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import { ConsoleButton, ConsoleFormCard } from '@/components/console';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';
import Select from 'primevue/select';
import MultiSelect from 'primevue/multiselect';
import InputNumber from 'primevue/inputnumber';
import ToggleSwitch from 'primevue/toggleswitch';
import Checkbox from 'primevue/checkbox';
import DatePicker from 'primevue/datepicker';
import SelectButton from 'primevue/selectbutton';
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

interface Props {
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
    teamMembers: TeamMember[];
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    description: '',
    event_type: 'one_time' as 'one_time' | 'recurring',
    location_type: 'in_person' as 'in_person' | 'virtual',
    location: '',
    virtual_meeting_url: '',
    duration_minutes: 60,
    capacity: null as number | null,
    price: 0,
    is_active: true,
    category_ids: [] as number[],
    team_member_ids: [] as number[],
    use_provider_defaults: true,
    requires_approval: undefined as boolean | undefined,
    deposit_type: null as string | null,
    deposit_amount: null as number | null,
    cancellation_policy: null as string | null,
    advance_booking_days: null as number | null,
    min_booking_notice_hours: null as number | null,
    allow_waitlist: false,
    max_spots_per_booking: 10,
    // One-time event
    occurrence_datetime: null as Date | null,
    // Recurrence settings
    recurrence: {
        frequency: 'weekly',
        interval: 1,
        days_of_week: [] as number[],
        time_of_day: '',
        starts_at: null as Date | null,
        ends_at: null as Date | null,
        max_occurrences: null as number | null,
    },
});

const showAdvancedSettings = ref(false);
const unlimitedCapacity = ref(true);

const eventTypeOptions = [
    { label: 'One-time', value: 'one_time', icon: 'pi pi-calendar' },
    { label: 'Recurring', value: 'recurring', icon: 'pi pi-replay' },
];

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

const submit = () => {
    // Format recurrence time for submission
    const formData = { ...form.data() };

    if (form.event_type === 'recurring' && form.recurrence.time_of_day) {
        // Keep time_of_day as HH:mm string
        formData.recurrence = {
            ...form.recurrence,
            starts_at: form.recurrence.starts_at ? formatDate(form.recurrence.starts_at) : null,
            ends_at: form.recurrence.ends_at ? formatDate(form.recurrence.ends_at) : null,
        };
    }

    if (form.event_type === 'one_time' && form.occurrence_datetime) {
        formData.occurrence_datetime = form.occurrence_datetime.toISOString();
    }

    form.transform(() => formData).post(resolveUrl(providerRoutes.events.store.url()), {
        preserveScroll: true,
    });
};

const formatDate = (date: Date): string => {
    return date.toISOString().split('T')[0];
};

const cancel = () => {
    router.get(resolveUrl(providerRoutes.events.index.url()));
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
    <ConsoleLayout title="Create Event">
        <div class="create-event-page">
            <!-- Header -->
            <div class="page-header">
                <button class="back-btn" @click="cancel">
                    <i class="pi pi-arrow-left"></i>
                </button>
                <div class="header-info">
                    <h1 class="page-title">Create Event</h1>
                    <p class="page-subtitle">Add a new event for attendees to register</p>
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
                    <!-- Media Info -->
                    <ConsoleFormCard title="Images & Videos">
                        <div class="media-info">
                            <i class="pi pi-image"></i>
                            <div>
                                <p class="media-info-title">Add media after creating your event</p>
                                <p class="media-info-text">
                                    You can upload a cover image, gallery photos, and videos once you save this event.
                                </p>
                            </div>
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
                                    :class="{ 'p-invalid': form.errors.description }"
                                />
                                <small v-if="form.errors.description" class="p-error">{{ form.errors.description }}</small>
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
                                <small class="form-hint">Optional - organize your events</small>
                            </div>
                        </div>
                    </ConsoleFormCard>

                    <!-- Event Type -->
                    <ConsoleFormCard title="Event Type">
                        <div class="form-grid">
                            <div class="form-field full-width">
                                <label class="form-label">Type *</label>
                                <SelectButton
                                    v-model="form.event_type"
                                    :options="eventTypeOptions"
                                    optionLabel="label"
                                    optionValue="value"
                                    class="event-type-selector"
                                >
                                    <template #option="{ option }">
                                        <i :class="option.icon"></i>
                                        <span>{{ option.label }}</span>
                                    </template>
                                </SelectButton>
                                <small class="form-hint">
                                    {{ form.event_type === 'one_time'
                                        ? 'Happens once at a specific date and time'
                                        : 'Repeats on a weekly schedule' }}
                                </small>
                            </div>

                            <!-- One-time Event Date/Time -->
                            <div v-if="form.event_type === 'one_time'" class="form-field full-width">
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

                            <!-- Recurring Event Settings -->
                            <template v-if="form.event_type === 'recurring'">
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
                                    <small v-if="form.errors['recurrence.days_of_week']" class="p-error">
                                        {{ form.errors['recurrence.days_of_week'] }}
                                    </small>
                                </div>

                                <div class="form-field">
                                    <label for="time_of_day" class="form-label">Time *</label>
                                    <InputText
                                        id="time_of_day"
                                        v-model="form.recurrence.time_of_day"
                                        type="time"
                                        class="form-input"
                                        :class="{ 'p-invalid': form.errors['recurrence.time_of_day'] }"
                                    />
                                    <small v-if="form.errors['recurrence.time_of_day']" class="p-error">
                                        {{ form.errors['recurrence.time_of_day'] }}
                                    </small>
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
                                    <label for="starts_at" class="form-label">Start Date *</label>
                                    <DatePicker
                                        id="starts_at"
                                        v-model="form.recurrence.starts_at"
                                        :minDate="minDate"
                                        placeholder="Select start date"
                                        class="form-input"
                                        :class="{ 'p-invalid': form.errors['recurrence.starts_at'] }"
                                    />
                                    <small v-if="form.errors['recurrence.starts_at']" class="p-error">
                                        {{ form.errors['recurrence.starts_at'] }}
                                    </small>
                                </div>

                                <div class="form-field">
                                    <label for="ends_at" class="form-label">End Date</label>
                                    <DatePicker
                                        id="ends_at"
                                        v-model="form.recurrence.ends_at"
                                        :minDate="form.recurrence.starts_at || minDate"
                                        placeholder="Optional end date"
                                        class="form-input"
                                    />
                                    <small class="form-hint">Leave empty for ongoing events</small>
                                </div>
                            </template>
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
                                <small class="form-hint">Set to $0 for free events</small>
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
                                <label for="max_spots_per_booking" class="form-label">Max Spots per Booking</label>
                                <InputNumber
                                    id="max_spots_per_booking"
                                    v-model="form.max_spots_per_booking"
                                    :min="1"
                                    :max="100"
                                    class="form-input"
                                />
                                <small class="form-hint">How many spots can one person book?</small>
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
                                        {{ form.requires_approval ? 'Manual approval required' : 'Auto-confirm' }}
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
                                        {{ form.allow_waitlist ? 'Waitlist enabled' : 'No waitlist' }}
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
                        <p v-if="form.team_member_ids.length === 0" class="sidebar-hint">
                            <i class="pi pi-info-circle"></i>
                            If none selected, assigned to you
                        </p>
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
                            label="Create Event"
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
.create-event-page {
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

/* Form */
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

/* Event Type Selector */
.event-type-selector :deep(.p-selectbutton) {
    display: flex;
    gap: 0.5rem;
}

.event-type-selector :deep(.p-button) {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    justify-content: center;
}

.location-type-selector :deep(.p-selectbutton) {
    display: flex;
    gap: 0.5rem;
}

.location-type-selector :deep(.p-button) {
    flex: 1;
    justify-content: center;
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
    border-color: var(--color-slate-300, #cbd5e1);
}

.day-btn.active {
    background-color: #106b4f;
    border-color: #106b4f;
    color: white;
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

/* Media Info */
.media-info {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1.25rem;
    background-color: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.media-info > i {
    font-size: 1.5rem;
    color: var(--color-slate-400, #94a3b8);
    flex-shrink: 0;
    margin-top: 0.125rem;
}

.media-info-title {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.media-info-text {
    margin: 0.25rem 0 0;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
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
    gap: 1rem;
}

.floating-buttons {
    display: flex;
    gap: 0.75rem;
}

@media (max-width: 639px) {
    .floating-actions-content {
        padding: 1rem;
    }

    .floating-buttons {
        width: 100%;
    }

    .floating-buttons :deep(button) {
        flex: 1;
    }
}

@media (min-width: 1024px) {
    .floating-actions {
        left: 280px;
    }
}

/* Team Sidebar */
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
}
</style>
