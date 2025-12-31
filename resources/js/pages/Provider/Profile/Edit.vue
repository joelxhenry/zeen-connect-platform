<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import SettingsLayout from '@/components/layout/SettingsLayout.vue';
import ConsoleFormCard from '@/components/console/ConsoleFormCard.vue';
import SingleImageUpload from '@/components/media/SingleImageUpload.vue';
import provider from '@/routes/provider';
import { resolveUrl } from '@/utils/url';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import InputText from 'primevue/inputtext';
import InputSwitch from 'primevue/inputswitch';
import Button from 'primevue/button';
import Calendar from 'primevue/calendar';
import Message from 'primevue/message';

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

interface ScheduleItem {
    id?: number;
    day_of_week: number;
    is_available: boolean;
    start_time: string | null;
    end_time: string | null;
    use_provider_defaults: boolean;
}

interface BreakItem {
    id: number;
    uuid: string;
    name: string | null;
    day_of_week: number;
    start_time: string;
    end_time: string;
}

interface BlockedDate {
    id: number;
    uuid: string;
    date: string;
    reason: string | null;
    is_recurring: boolean;
}

interface Availability {
    schedule: ScheduleItem[];
    breaks: BreakItem[];
    blockedDates: BlockedDate[];
}

interface ProviderDefaults {
    availability: {
        day_of_week: number;
        is_available: boolean;
        start_time: string | null;
        end_time: string | null;
    }[];
}

const props = defineProps<{
    user: UserData;
    teamMember: TeamMemberData;
    isOwner: boolean;
    availability: Availability;
    providerDefaults: ProviderDefaults;
}>();

const activeTab = ref(0);
const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

// ============================================
// PERSONAL INFO TAB
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
// AVAILABILITY TAB
// ============================================

// Build initial schedule with defaults for missing days
const buildInitialSchedule = (): ScheduleItem[] => {
    const schedule: ScheduleItem[] = [];
    for (let day = 0; day < 7; day++) {
        const existing = props.availability.schedule.find(s => s.day_of_week === day);
        const providerDefault = props.providerDefaults.availability.find(a => a.day_of_week === day);

        if (existing) {
            schedule.push({ ...existing });
        } else {
            // Use provider defaults for new entries
            schedule.push({
                day_of_week: day,
                is_available: providerDefault?.is_available ?? (day >= 1 && day <= 5),
                start_time: providerDefault?.start_time ?? '09:00',
                end_time: providerDefault?.end_time ?? '17:00',
                use_provider_defaults: true,
            });
        }
    }
    return schedule;
};

const scheduleForm = useForm({
    schedule: buildInitialSchedule(),
});

const saveSchedule = () => {
    scheduleForm.put(resolveUrl(provider.profile.availability.url()), {
        preserveScroll: true,
        onSuccess: () => {
            scheduleForm.defaults({
                schedule: scheduleForm.schedule.map(s => ({ ...s })),
            });
            scheduleForm.reset();
            router.reload({ only: ['availability'] });
        },
    });
};

// Breaks
const showAddBreak = ref(false);
const newBreak = ref({
    name: 'Lunch',
    day_of_week: 1,
    start_time: '12:00',
    end_time: '13:00',
});

const addBreak = () => {
    router.post(resolveUrl(provider.profile['breaks.add'].url()), {
        name: newBreak.value.name,
        day_of_week: newBreak.value.day_of_week,
        start_time: newBreak.value.start_time,
        end_time: newBreak.value.end_time,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showAddBreak.value = false;
            newBreak.value = {
                name: 'Lunch',
                day_of_week: 1,
                start_time: '12:00',
                end_time: '13:00',
            };
        },
    });
};

const removeBreak = (breakId: number) => {
    router.delete(resolveUrl(provider.profile['breaks.remove'].url()), {
        data: { break_id: breakId },
        preserveScroll: true,
    });
};

// Blocked Dates
const newBlockedDate = ref<Date | null>(null);
const newBlockedReason = ref('');

const addBlockedDate = () => {
    if (!newBlockedDate.value) return;

    router.post(resolveUrl(provider.profile['blocked-dates.add'].url()), {
        date: newBlockedDate.value.toISOString().split('T')[0],
        reason: newBlockedReason.value || null,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            newBlockedDate.value = null;
            newBlockedReason.value = '';
        },
    });
};

const removeBlockedDate = (blockedDateId: number) => {
    router.delete(resolveUrl(provider.profile['blocked-dates.remove'].url()), {
        data: { blocked_date_id: blockedDateId },
        preserveScroll: true,
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
    return personalForm.isDirty || scheduleForm.isDirty;
});

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
            <TabView v-model:activeIndex="activeTab" class="profile-tabs">
                <!-- ================================ -->
                <!-- PERSONAL INFO TAB -->
                <!-- ================================ -->
                <TabPanel value="0" header="Personal Info">
                    <div class="tab-content">
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

                            <div v-if="personalForm.isDirty" class="form-actions">
                                <Button
                                    label="Discard"
                                    severity="secondary"
                                    text
                                    @click="personalForm.reset()"
                                />
                                <Button
                                    label="Save Changes"
                                    :loading="personalForm.processing"
                                    @click="savePersonalInfo"
                                />
                            </div>
                        </ConsoleFormCard>

                        <!-- Link to My Brand -->
                        <Message severity="info" :closable="false" class="info-banner">
                            <div class="banner-content">
                                <i class="pi pi-info-circle"></i>
                                <span>
                                    Looking to update your business name, bio, or branding?
                                    <a href="#" @click.prevent="router.visit(resolveUrl(provider.branding.edit.url()))">
                                        Go to My Brand
                                    </a>
                                </span>
                            </div>
                        </Message>
                    </div>
                </TabPanel>

                <!-- ================================ -->
                <!-- CALENDAR SYNC TAB -->
                <!-- ================================ -->
                <TabPanel value="1" header="Calendar Sync">
                    <div class="tab-content">
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
                                <Button
                                    label="Notify Me When Available"
                                    icon="pi pi-bell"
                                    outlined
                                    disabled
                                />
                            </div>
                        </ConsoleFormCard>
                    </div>
                </TabPanel>

                <!-- ================================ -->
                <!-- AVAILABILITY TAB -->
                <!-- ================================ -->
                <TabPanel value="2" header="Availability">
                    <div class="tab-content">
                        <!-- Working Hours -->
                        <ConsoleFormCard title="Working Hours">
                            <template #header-actions>
                                <Button
                                    v-if="scheduleForm.isDirty"
                                    label="Save"
                                    size="small"
                                    :loading="scheduleForm.processing"
                                    @click="saveSchedule"
                                />
                            </template>

                            <p class="section-description">
                                Set your regular working hours for each day of the week.
                                <template v-if="isOwner">
                                    These settings override the business defaults for your personal schedule.
                                </template>
                            </p>

                            <div class="schedule-grid">
                                <div
                                    v-for="(day, index) in scheduleForm.schedule"
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
                                <Button
                                    label="Add Break"
                                    icon="pi pi-plus"
                                    size="small"
                                    text
                                    @click="showAddBreak = true"
                                />
                            </template>

                            <p class="section-description">
                                Add regular breaks like lunch or personal time. These slots will be blocked for bookings.
                            </p>

                            <div v-if="availability.breaks.length === 0 && !showAddBreak" class="empty-state">
                                <p>No breaks configured. Add breaks to block off time for lunch or personal activities.</p>
                            </div>

                            <div v-else class="breaks-list">
                                <div
                                    v-for="breakItem in availability.breaks"
                                    :key="breakItem.id"
                                    class="break-item"
                                >
                                    <div class="break-day">{{ dayNames[breakItem.day_of_week] }}</div>
                                    <div class="break-time">
                                        {{ breakItem.start_time }} - {{ breakItem.end_time }}
                                    </div>
                                    <div class="break-label">{{ breakItem.name || 'Break' }}</div>
                                    <Button
                                        icon="pi pi-trash"
                                        text
                                        rounded
                                        severity="danger"
                                        size="small"
                                        @click="removeBreak(breakItem.id)"
                                    />
                                </div>
                            </div>

                            <!-- Add Break Form -->
                            <div v-if="showAddBreak" class="add-break-form">
                                <div class="form-row">
                                    <select v-model.number="newBreak.day_of_week" class="day-select">
                                        <option v-for="(name, idx) in dayNames" :key="idx" :value="idx">
                                            {{ name }}
                                        </option>
                                    </select>
                                    <input type="time" v-model="newBreak.start_time" class="time-input" />
                                    <span class="time-separator">to</span>
                                    <input type="time" v-model="newBreak.end_time" class="time-input" />
                                    <InputText
                                        v-model="newBreak.name"
                                        placeholder="Label (optional)"
                                        class="label-input"
                                    />
                                </div>
                                <div class="form-actions-inline">
                                    <Button
                                        label="Cancel"
                                        text
                                        size="small"
                                        @click="showAddBreak = false"
                                    />
                                    <Button
                                        label="Add"
                                        size="small"
                                        @click="addBreak"
                                    />
                                </div>
                            </div>
                        </ConsoleFormCard>

                        <!-- Time Off -->
                        <ConsoleFormCard title="Time Off">
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
                                <Button
                                    label="Add"
                                    icon="pi pi-plus"
                                    size="small"
                                    :disabled="!newBlockedDate"
                                    @click="addBlockedDate"
                                />
                            </div>

                            <div v-if="availability.blockedDates.length > 0" class="blocked-dates-list">
                                <div
                                    v-for="blocked in availability.blockedDates"
                                    :key="blocked.id"
                                    class="blocked-date-item"
                                >
                                    <div class="blocked-date">{{ formatDate(blocked.date) }}</div>
                                    <div class="blocked-reason">{{ blocked.reason || 'No reason' }}</div>
                                    <Button
                                        icon="pi pi-trash"
                                        text
                                        rounded
                                        severity="danger"
                                        size="small"
                                        @click="removeBlockedDate(blocked.id)"
                                    />
                                </div>
                            </div>

                            <div v-else class="empty-state">
                                <p>No time off scheduled. Add dates above when you won't be available.</p>
                            </div>
                        </ConsoleFormCard>
                    </div>
                </TabPanel>
            </TabView>
        </div>
    </SettingsLayout>
</template>

<style scoped>
.profile-page {
    max-width: 800px;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

@media (min-width: 640px) {
    .profile-page {
        gap: 1.5rem;
    }
}

/* Tab Styling */
.profile-tabs :deep(.p-tabview-panels) {
    padding: 0;
    background: transparent;
}

.profile-tabs :deep(.p-tabview-nav) {
    background: transparent;
    border: none;
    gap: 0.125rem;
    flex-wrap: wrap;
}

@media (min-width: 640px) {
    .profile-tabs :deep(.p-tabview-nav) {
        gap: 0.25rem;
        flex-wrap: nowrap;
    }
}

.profile-tabs :deep(.p-tabview-nav-link) {
    background: transparent;
    border: none;
    border-radius: 0.5rem;
    padding: 0.5rem 0.75rem;
    color: var(--color-slate-600, #475569);
    font-weight: 500;
    font-size: 0.8125rem;
}

@media (min-width: 640px) {
    .profile-tabs :deep(.p-tabview-nav-link) {
        padding: 0.75rem 1rem;
        font-size: 0.875rem;
    }
}

.profile-tabs :deep(.p-tabview-nav-link:hover) {
    background: var(--color-slate-100, #f1f5f9);
}

.profile-tabs :deep(.p-tabview-selected .p-tabview-nav-link) {
    background: white;
    color: #106B4F;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.tab-content {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding-top: 0.75rem;
}

@media (min-width: 640px) {
    .tab-content {
        gap: 1.5rem;
        padding-top: 1rem;
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

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--color-slate-100, #f1f5f9);
}

/* Info Banner */
.info-banner {
    margin: 0;
}

.banner-content {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.banner-content a {
    color: inherit;
    font-weight: 600;
    text-decoration: underline;
}

.banner-content a:hover {
    text-decoration: none;
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
    display: grid;
    grid-template-columns: auto 1fr;
    grid-template-rows: auto auto;
    gap: 0.5rem 0.75rem;
    padding: 0.75rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    transition: opacity 0.15s ease;
}

@media (min-width: 640px) {
    .day-row {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
    }
}

.day-row.inactive {
    opacity: 0.6;
}

.day-toggle {
    flex-shrink: 0;
    grid-row: 1 / 3;
    display: flex;
    align-items: center;
}

.day-name {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
    align-self: center;
}

@media (min-width: 640px) {
    .day-name {
        width: 100px;
    }
}

.day-times {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex: 1;
    grid-column: 2;
    flex-wrap: wrap;
}

.time-input {
    padding: 0.5rem;
    font-size: 0.875rem;
    border: 1px solid var(--color-slate-300, #cbd5e1);
    border-radius: 0.375rem;
    background: white;
    width: 100%;
    max-width: 120px;
}

@media (min-width: 640px) {
    .time-input {
        width: auto;
    }
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
    grid-column: 2;
}

/* Breaks */
.breaks-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.break-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.25rem 0.5rem;
    padding: 0.75rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

@media (min-width: 640px) {
    .break-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
    }
}

.break-day {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

@media (min-width: 640px) {
    .break-day {
        width: 100px;
    }
}

.break-time {
    font-size: 0.8125rem;
    color: var(--color-slate-600, #475569);
    font-family: ui-monospace, monospace;
    grid-column: 1;
}

@media (min-width: 640px) {
    .break-time {
        font-size: 0.875rem;
    }
}

.break-label {
    flex: 1;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    grid-column: 1;
}

@media (min-width: 640px) {
    .break-label {
        font-size: 0.875rem;
    }
}

.break-item :deep(.p-button) {
    grid-row: 1 / 4;
    grid-column: 2;
    align-self: center;
}

@media (min-width: 640px) {
    .break-item :deep(.p-button) {
        grid-row: auto;
        grid-column: auto;
    }
}

.add-break-form {
    margin-top: 1rem;
    padding: 0.75rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

@media (min-width: 640px) {
    .add-break-form {
        padding: 1rem;
    }
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

@media (min-width: 640px) {
    .form-row {
        gap: 0.75rem;
    }
}

.day-select {
    padding: 0.5rem;
    font-size: 0.875rem;
    border: 1px solid var(--color-slate-300, #cbd5e1);
    border-radius: 0.375rem;
    background: white;
    width: 100%;
}

@media (min-width: 640px) {
    .day-select {
        width: auto;
    }
}

.label-input {
    flex: 1;
    min-width: 100%;
}

@media (min-width: 640px) {
    .label-input {
        min-width: 120px;
    }
}

.form-actions-inline {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
}

/* Blocked Dates */
.add-blocked-date {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

@media (min-width: 640px) {
    .add-blocked-date {
        flex-direction: row;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }
}

.date-picker {
    width: 100%;
}

@media (min-width: 640px) {
    .date-picker {
        width: 180px;
    }
}

.reason-input {
    flex: 1;
    width: 100%;
}

@media (min-width: 640px) {
    .reason-input {
        width: auto;
        min-width: 150px;
    }
}

.add-blocked-date :deep(.p-button) {
    width: 100%;
}

@media (min-width: 640px) {
    .add-blocked-date :deep(.p-button) {
        width: auto;
    }
}

.blocked-dates-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.blocked-date-item {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 0.25rem 0.5rem;
    padding: 0.75rem;
    background: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
}

@media (min-width: 640px) {
    .blocked-date-item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 0.75rem 1rem;
    }
}

.blocked-date {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-700, #334155);
}

.blocked-reason {
    flex: 1;
    font-size: 0.8125rem;
    color: var(--color-slate-500, #64748b);
    grid-column: 1;
}

@media (min-width: 640px) {
    .blocked-reason {
        font-size: 0.875rem;
    }
}

.blocked-date-item :deep(.p-button) {
    grid-row: 1 / 3;
    grid-column: 2;
    align-self: center;
}

@media (min-width: 640px) {
    .blocked-date-item :deep(.p-button) {
        grid-row: auto;
        grid-column: auto;
    }
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
</style>
