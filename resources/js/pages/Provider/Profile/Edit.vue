<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SettingsLayout from '@/components/layout/SettingsLayout.vue';
import ConsoleFormCard from '@/components/console/ConsoleFormCard.vue';
import { ConsoleButton } from '@/components/console';
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import InputText from 'primevue/inputtext';
import InputSwitch from 'primevue/inputswitch';
import Calendar from 'primevue/calendar';

interface MediaItem {
    id: number;
    uuid: string;
    url: string;
    thumbnail: string;
    medium: string;
    filename: string;
}

interface UserData {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    avatar_url: string | null;
    avatar_media: MediaItem | null;
}

interface TeamMemberData {
    id: number;
    uuid: string;
    title: string | null;
}

interface DaySchedule {
    day_of_week: number;
    start_time: string;
    end_time: string;
    is_available: boolean;
}

interface BlockedDate {
    id?: number;
    date: string;
    reason: string | null;
}

interface Break {
    id?: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    label: string | null;
}

const props = defineProps<{
    user: UserData;
    teamMember: TeamMemberData;
    isOwner: boolean;
    weeklySchedule: DaySchedule[];
    blockedDates: BlockedDate[];
    breaks: Break[];
}>();

// ============================================
// SECTION NAVIGATION
// ============================================
type Section = 'personal' | 'availability' | 'calendar';
const activeSection = ref<Section>('personal');

const sections = [
    { key: 'personal' as Section, label: 'Personal Info', icon: 'pi pi-user' },
    { key: 'availability' as Section, label: 'Availability', icon: 'pi pi-clock' },
    { key: 'calendar' as Section, label: 'Calendar Sync', icon: 'pi pi-calendar' },
];

// ============================================
// PERSONAL INFO
// ============================================
const avatarMedia = ref<MediaItem | null>(props.user.avatar_media);

const personalForm = useForm({
    name: props.user.name || '',
    phone: props.user.phone || '',
    title: props.teamMember.title || '',
});

const savePersonalInfo = () => {
    personalForm.put(resolveUrl(provider.profile.update.url()), {
        preserveScroll: true,
        onSuccess: () => {
            personalForm.defaults({
                name: personalForm.name,
                phone: personalForm.phone,
                title: personalForm.title,
            });
            personalForm.reset();
        },
    });
};

const handleAvatarUploaded = () => {
    router.reload({ only: ['user'] });
};

// ============================================
// AVAILABILITY
// ============================================
const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// Schedule form
const scheduleForm = useForm({
    schedule: props.weeklySchedule.map(day => ({ ...day })),
});

// Blocked dates form
const blockedDatesForm = useForm({
    blocked_dates: props.blockedDates.map(d => ({ ...d })),
});

// Breaks form
const breaksForm = useForm({
    breaks: props.breaks.map(b => ({ ...b })),
});

// New blocked date input
const newBlockedDate = ref<Date | null>(null);
const newBlockedReason = ref('');

// New break input
const showAddBreak = ref(false);
const newBreak = ref<Break>({
    day_of_week: 1,
    start_time: '12:00',
    end_time: '13:00',
    label: 'Lunch',
});

const saveSchedule = () => {
    scheduleForm.put(resolveUrl(provider.availability.schedule.url()), {
        preserveScroll: true,
        onSuccess: () => {
            scheduleForm.defaults({
                schedule: scheduleForm.schedule.map(day => ({ ...day })),
            });
            scheduleForm.reset();
        },
    });
};

const addBlockedDate = () => {
    if (!newBlockedDate.value) return;

    const dateStr = newBlockedDate.value.toISOString().split('T')[0];

    // Check if date already exists
    if (blockedDatesForm.blocked_dates.some(d => d.date === dateStr)) {
        return;
    }

    blockedDatesForm.blocked_dates.push({
        date: dateStr,
        reason: newBlockedReason.value || null,
    });

    newBlockedDate.value = null;
    newBlockedReason.value = '';
};

const removeBlockedDate = (index: number) => {
    blockedDatesForm.blocked_dates.splice(index, 1);
};

const saveBlockedDates = () => {
    blockedDatesForm.put(resolveUrl(provider.availability.blockedDates.url()), {
        preserveScroll: true,
        onSuccess: () => {
            blockedDatesForm.defaults({
                blocked_dates: blockedDatesForm.blocked_dates.map(d => ({ ...d })),
            });
            blockedDatesForm.reset();
        },
    });
};

const addBreak = () => {
    breaksForm.breaks.push({ ...newBreak.value });
    showAddBreak.value = false;
    newBreak.value = {
        day_of_week: 1,
        start_time: '12:00',
        end_time: '13:00',
        label: 'Lunch',
    };
};

const removeBreak = (index: number) => {
    breaksForm.breaks.splice(index, 1);
};

const saveBreaks = () => {
    breaksForm.put(resolveUrl(provider.availability.breaks.url()), {
        preserveScroll: true,
        onSuccess: () => {
            breaksForm.defaults({
                breaks: breaksForm.breaks.map(b => ({ ...b })),
            });
            breaksForm.reset();
        },
    });
};

const formatDate = (dateStr: string) => {
    const date = new Date(dateStr + 'T00:00:00');
    return date.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric' });
};

// ============================================
// DIRTY STATE & NAVIGATION WARNING
// ============================================
const isDirty = computed(() => {
    return personalForm.isDirty || scheduleForm.isDirty || blockedDatesForm.isDirty || breaksForm.isDirty;
});

const isProcessing = computed(() => {
    return personalForm.processing || scheduleForm.processing || blockedDatesForm.processing || breaksForm.processing;
});

const saveAllChanges = () => {
    if (personalForm.isDirty) {
        savePersonalInfo();
    }
    if (scheduleForm.isDirty) {
        saveSchedule();
    }
    if (blockedDatesForm.isDirty) {
        saveBlockedDates();
    }
    if (breaksForm.isDirty) {
        saveBreaks();
    }
};

const discardAllChanges = () => {
    personalForm.reset();
    scheduleForm.reset();
    blockedDatesForm.reset();
    breaksForm.reset();
};

const beforeUnloadHandler = (e: BeforeUnloadEvent) => {
    if (isDirty.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

onMounted(() => {
    window.addEventListener('beforeunload', beforeUnloadHandler);
});

onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', beforeUnloadHandler);
});
</script>

<template>
    <SettingsLayout title="Your Profile">
        <div class="profile-page">
            <!-- Section Toggle Navigation -->
            <div class="section-nav">
                <button
                    v-for="section in sections"
                    :key="section.key"
                    type="button"
                    class="section-btn"
                    :class="{ active: activeSection === section.key }"
                    @click="activeSection = section.key"
                >
                    <i :class="section.icon"></i>
                    <span>{{ section.label }}</span>
                </button>
            </div>

            <!-- ================================ -->
            <!-- PERSONAL INFO SECTION -->
            <!-- ================================ -->
            <template v-if="activeSection === 'personal'">
                <!-- Avatar Upload -->
                <ConsoleFormCard title="Profile Photo">
                    <div class="avatar-section">
                        <SingleImageUpload
                            v-model="avatarMedia"
                            :uploadUrl="resolveUrl(provider.media.upload.url())"
                            collection="avatar"
                            shape="circle"
                            placeholder="Upload Photo"
                            @uploaded="handleAvatarUploaded"
                        />
                        <div class="avatar-hints">
                            <p>Your photo appears next to your bookings and in team views.</p>
                            <ul>
                                <li>Recommended size: 200x200 pixels</li>
                                <li>Formats: PNG, JPG, WebP</li>
                                <li>Maximum file size: 2MB</li>
                            </ul>
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Personal Information -->
                <ConsoleFormCard title="Personal Information">
                    <div class="form-grid">
                        <div class="form-field">
                            <label for="name" class="form-label">Full Name *</label>
                            <InputText
                                id="name"
                                v-model="personalForm.name"
                                :class="{ 'p-invalid': personalForm.errors.name }"
                                placeholder="Your full name"
                                class="w-full"
                            />
                            <small v-if="personalForm.errors.name" class="p-error">
                                {{ personalForm.errors.name }}
                            </small>
                        </div>

                        <div class="form-field">
                            <label for="title" class="form-label">Role / Title</label>
                            <InputText
                                id="title"
                                v-model="personalForm.title"
                                :class="{ 'p-invalid': personalForm.errors.title }"
                                placeholder="e.g., Senior Stylist, Owner"
                                class="w-full"
                            />
                            <small v-if="personalForm.errors.title" class="p-error">
                                {{ personalForm.errors.title }}
                            </small>
                        </div>

                        <div class="form-field">
                            <label for="email" class="form-label">Email</label>
                            <InputText
                                id="email"
                                :modelValue="user.email"
                                disabled
                                class="w-full"
                            />
                            <small class="form-hint">Email cannot be changed here</small>
                        </div>

                        <div class="form-field">
                            <label for="phone" class="form-label">Phone</label>
                            <InputText
                                id="phone"
                                v-model="personalForm.phone"
                                :class="{ 'p-invalid': personalForm.errors.phone }"
                                placeholder="Your phone number"
                                class="w-full"
                            />
                            <small v-if="personalForm.errors.phone" class="p-error">
                                {{ personalForm.errors.phone }}
                            </small>
                        </div>
                    </div>
                </ConsoleFormCard>
            </template>

            <!-- ================================ -->
            <!-- AVAILABILITY SECTION -->
            <!-- ================================ -->
            <template v-if="activeSection === 'availability'">
                <!-- Working Hours -->
                <ConsoleFormCard title="Working Hours">
                    <template #header-actions>
                        <ConsoleButton
                            v-if="scheduleForm.isDirty"
                            label="Save"
                            variant="primary"
                            size="small"
                            :loading="scheduleForm.processing"
                            @click="saveSchedule"
                        />
                    </template>

                    <p class="section-description">
                        Set your regular working hours for each day of the week.
                    </p>

                    <div class="schedule-grid">
                        <div
                            v-for="day in scheduleForm.schedule"
                            :key="day.day_of_week"
                            class="day-row"
                            :class="{ inactive: !day.is_available }"
                        >
                            <div class="day-toggle">
                                <InputSwitch v-model="day.is_available" />
                            </div>
                            <div class="day-name">{{ dayNames[day.day_of_week] }}</div>
                            <div v-if="day.is_available" class="day-times">
                                <input
                                    type="time"
                                    v-model="day.start_time"
                                    class="time-input"
                                />
                                <span class="time-separator">to</span>
                                <input
                                    type="time"
                                    v-model="day.end_time"
                                    class="time-input"
                                />
                            </div>
                            <div v-else class="day-unavailable">Unavailable</div>
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Breaks -->
                <ConsoleFormCard title="Breaks">
                    <template #header-actions>
                        <ConsoleButton
                            v-if="breaksForm.isDirty"
                            label="Save"
                            variant="primary"
                            size="small"
                            :loading="breaksForm.processing"
                            @click="saveBreaks"
                            class="mr-2"
                        />
                        <ConsoleButton
                            label="Add Break"
                            icon="pi pi-plus"
                            variant="text"
                            size="small"
                            @click="showAddBreak = true"
                        />
                    </template>

                    <p class="section-description">
                        Add regular breaks like lunch or personal time. These slots will be blocked for bookings.
                    </p>

                    <div v-if="breaksForm.breaks.length === 0 && !showAddBreak" class="empty-state">
                        <p>No breaks configured. Add breaks to block off time for lunch or personal activities.</p>
                    </div>

                    <div v-else class="breaks-list">
                        <div
                            v-for="(breakItem, index) in breaksForm.breaks"
                            :key="index"
                            class="break-item"
                        >
                            <div class="break-day">{{ dayNames[breakItem.day_of_week] }}</div>
                            <div class="break-time">
                                {{ breakItem.start_time }} - {{ breakItem.end_time }}
                            </div>
                            <div class="break-label">{{ breakItem.label || 'Break' }}</div>
                            <ConsoleButton
                                icon="pi pi-trash"
                                variant="text"
                                severity="danger"
                                rounded
                                size="small"
                                @click="removeBreak(index)"
                            />
                        </div>
                    </div>

                    <!-- Add Break Form -->
                    <div v-if="showAddBreak" class="add-break-form">
                        <div class="break-form-row">
                            <select v-model.number="newBreak.day_of_week" class="day-select">
                                <option v-for="(name, idx) in dayNames" :key="idx" :value="idx">
                                    {{ name }}
                                </option>
                            </select>
                            <input type="time" v-model="newBreak.start_time" class="time-input" />
                            <span class="time-separator">to</span>
                            <input type="time" v-model="newBreak.end_time" class="time-input" />
                            <InputText
                                v-model="newBreak.label"
                                placeholder="Label (optional)"
                                class="label-input"
                            />
                        </div>
                        <div class="break-form-actions">
                            <ConsoleButton
                                label="Cancel"
                                variant="text"
                                severity="secondary"
                                size="small"
                                @click="showAddBreak = false"
                            />
                            <ConsoleButton
                                label="Add"
                                variant="primary"
                                size="small"
                                @click="addBreak"
                            />
                        </div>
                    </div>
                </ConsoleFormCard>

                <!-- Time Off -->
                <ConsoleFormCard title="Time Off">
                    <template #header-actions>
                        <ConsoleButton
                            v-if="blockedDatesForm.isDirty"
                            label="Save"
                            variant="primary"
                            size="small"
                            :loading="blockedDatesForm.processing"
                            @click="saveBlockedDates"
                        />
                    </template>

                    <p class="section-description">
                        Block specific dates when you won't be available for bookings.
                    </p>

                    <div class="add-blocked-date">
                        <Calendar
                            v-model="newBlockedDate"
                            :minDate="new Date()"
                            dateFormat="M d, yy"
                            placeholder="Select date"
                            showIcon
                            class="date-picker"
                        />
                        <InputText
                            v-model="newBlockedReason"
                            placeholder="Reason (optional)"
                            class="reason-input"
                        />
                        <ConsoleButton
                            label="Add"
                            icon="pi pi-plus"
                            variant="primary"
                            size="small"
                            :disabled="!newBlockedDate"
                            @click="addBlockedDate"
                        />
                    </div>

                    <div v-if="blockedDatesForm.blocked_dates.length > 0" class="blocked-dates-list">
                        <div
                            v-for="(blocked, index) in blockedDatesForm.blocked_dates"
                            :key="index"
                            class="blocked-date-item"
                        >
                            <div class="blocked-date">{{ formatDate(blocked.date) }}</div>
                            <div class="blocked-reason">{{ blocked.reason || 'No reason' }}</div>
                            <ConsoleButton
                                icon="pi pi-trash"
                                variant="text"
                                severity="danger"
                                rounded
                                size="small"
                                @click="removeBlockedDate(index)"
                            />
                        </div>
                    </div>

                    <div v-else class="empty-state">
                        <p>No time off scheduled. Add dates above when you won't be available.</p>
                    </div>
                </ConsoleFormCard>
            </template>

            <!-- ================================ -->
            <!-- CALENDAR SYNC SECTION -->
            <!-- ================================ -->
            <template v-if="activeSection === 'calendar'">
                <ConsoleFormCard title="Calendar Integration">
                    <div class="coming-soon-card">
                        <div class="coming-soon-icon">
                            <i class="pi pi-calendar"></i>
                        </div>
                        <h3>Coming Soon</h3>
                        <p>
                            Connect your Google Calendar or Outlook to automatically sync your
                            availability and bookings.
                        </p>
                        <ul class="feature-list">
                            <li><i class="pi pi-check"></i> Automatically block busy times</li>
                            <li><i class="pi pi-check"></i> Sync bookings to your calendar</li>
                            <li><i class="pi pi-check"></i> Avoid double-bookings</li>
                            <li><i class="pi pi-check"></i> Two-way sync with your calendar</li>
                        </ul>
                        <ConsoleButton
                            label="Notify Me When Available"
                            icon="pi pi-bell"
                            variant="outlined"
                            severity="secondary"
                            disabled
                        />
                    </div>
                </ConsoleFormCard>
            </template>

            <!-- Spacer for floating footer -->
            <div class="form-footer-spacer"></div>
        </div>

        <!-- Floating Action Bar -->
        <Transition name="slide-up">
            <div v-if="isDirty" class="floating-actions">
                <div class="floating-actions-content">
                    <div class="floating-info">
                        <i class="pi pi-info-circle"></i>
                        <span>You have unsaved changes</span>
                    </div>
                    <div class="floating-buttons">
                        <ConsoleButton
                            type="button"
                            label="Discard"
                            variant="secondary"
                            size="small"
                            @click="discardAllChanges"
                        />
                        <ConsoleButton
                            type="button"
                            label="Save Changes"
                            icon="pi pi-check"
                            variant="primary"
                            size="small"
                            :loading="isProcessing"
                            @click="saveAllChanges"
                        />
                    </div>
                </div>
            </div>
        </Transition>
    </SettingsLayout>
</template>

<style scoped>
.profile-page {
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

/* Section Navigation Toggle Buttons */
.section-nav {
    display: flex;
    gap: 0.5rem;
    padding: 0.25rem;
    background: var(--color-slate-100, #f1f5f9);
    border-radius: 0.75rem;
}

.section-btn {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: transparent;
    border: none;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-600, #475569);
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.section-btn:hover {
    color: var(--color-slate-900, #0f172a);
    background: rgba(255, 255, 255, 0.5);
}

.section-btn.active {
    background: white;
    color: #106B4F;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.section-btn i {
    font-size: 1rem;
}

@media (max-width: 639px) {
    .section-btn {
        padding: 0.625rem 0.75rem;
        font-size: 0.8125rem;
    }

    .section-btn i {
        font-size: 0.875rem;
    }
}

/* Avatar Section */
.avatar-section {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
}

@media (min-width: 640px) {
    .avatar-section {
        flex-direction: row;
        gap: 1.5rem;
        align-items: flex-start;
    }
}

.avatar-hints {
    flex: 1;
    text-align: center;
}

@media (min-width: 640px) {
    .avatar-hints {
        text-align: left;
    }
}

.avatar-hints p {
    margin: 0 0 0.75rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.avatar-hints ul {
    margin: 0;
    padding-left: 1.25rem;
    text-align: left;
    display: inline-block;
}

.avatar-hints li {
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.6;
}

/* Form Grid */
.form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.25rem;
}

@media (min-width: 640px) {
    .form-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.form-hint {
    font-size: 0.75rem;
    color: var(--color-slate-500, #64748b);
}

.w-full {
    width: 100%;
}

/* Section Description */
.section-description {
    margin: 0 0 1.25rem;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
    line-height: 1.5;
}

/* Schedule Grid */
.schedule-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.day-row {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    transition: opacity 0.15s ease;
}

.day-row.inactive {
    opacity: 0.6;
}

.day-toggle {
    flex-shrink: 0;
}

.day-name {
    width: 100px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.day-times {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
}

.time-input {
    padding: 0.5rem;
    font-size: 0.875rem;
    border: 1px solid var(--color-slate-300, #cbd5e1);
    border-radius: 0.375rem;
    background: white;
}

.time-input:focus {
    outline: none;
    border-color: #106B4F;
}

.time-separator {
    font-size: 0.875rem;
    color: var(--color-slate-400, #94a3b8);
}

.day-unavailable {
    font-size: 0.875rem;
    color: var(--color-slate-400, #94a3b8);
    font-style: italic;
}

/* Breaks */
.breaks-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.break-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.break-day {
    width: 100px;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.break-time {
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
    font-family: ui-monospace, monospace;
}

.break-label {
    flex: 1;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.add-break-form {
    margin-top: 1rem;
    padding: 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.break-form-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.day-select {
    padding: 0.5rem;
    font-size: 0.875rem;
    border: 1px solid var(--color-slate-300, #cbd5e1);
    border-radius: 0.375rem;
    background: white;
}

.label-input {
    flex: 1;
    min-width: 120px;
}

.break-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* Blocked Dates */
.add-blocked-date {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    align-items: center;
    margin-bottom: 1rem;
}

.date-picker {
    width: 180px;
}

.reason-input {
    flex: 1;
    min-width: 150px;
}

.blocked-dates-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.blocked-date-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.blocked-date {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.blocked-reason {
    flex: 1;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

/* Empty State */
.empty-state {
    padding: 1.5rem;
    text-align: center;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

.empty-state p {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.mr-2 {
    margin-right: 0.5rem;
}

/* Coming Soon Card */
.coming-soon-card {
    text-align: center;
    padding: 2rem;
}

.coming-soon-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    background: var(--color-slate-100, #f1f5f9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.coming-soon-icon i {
    font-size: 1.5rem;
    color: #106B4F;
}

.coming-soon-card h3 {
    margin: 0 0 0.5rem;
    font-size: 1.25rem;
    color: var(--color-slate-900, #0f172a);
}

.coming-soon-card p {
    margin: 0 0 1.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1.5rem;
    text-align: left;
    max-width: 300px;
    margin-left: auto;
    margin-right: auto;
}

.feature-list li {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 0;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.feature-list li i {
    color: #22c55e;
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
    max-width: 800px;
    margin: 0 auto;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.floating-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.floating-info i {
    color: #106B4F;
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

@media (max-width: 639px) {
    .floating-actions-content {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
        padding: 1rem;
    }

    .floating-info {
        justify-content: center;
    }

    .floating-buttons {
        display: grid;
        grid-template-columns: 1fr 1fr;
    }
}

/* Slide up animation */
.slide-up-enter-active,
.slide-up-leave-active {
    transition: transform 0.3s ease, opacity 0.3s ease;
}

.slide-up-enter-from,
.slide-up-leave-to {
    transform: translateY(100%);
    opacity: 0;
}
</style>
