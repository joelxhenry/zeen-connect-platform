<script setup lang="ts">
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import DashboardLayout from '@/components/layout/DashboardLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';

interface BookingData {
    id: number;
    uuid: string;
    provider: {
        business_name: string;
        slug: string;
        avatar?: string;
        location?: string;
        address?: string;
    };
    service: {
        name: string;
        description?: string;
        duration_minutes: number;
    };
    booking_date: string;
    formatted_date: string;
    formatted_time: string;
    status: string;
    status_label: string;
    status_color: string;
    service_price: number;
    total_amount: number;
    total_display: string;
    client_notes?: string;
    provider_notes?: string;
    cancellation_reason?: string;
    is_past: boolean;
    is_today: boolean;
    can_cancel: boolean;
    confirmed_at?: string;
    completed_at?: string;
    cancelled_at?: string;
    created_at: string;
}

const props = defineProps<{
    booking: BookingData;
}>();

const page = usePage();
const showCancelDialog = ref(false);

const cancelForm = useForm({
    reason: '',
});

const cancelBooking = () => {
    cancelForm.post(route('client.bookings.cancel', props.booking.uuid), {
        onSuccess: () => {
            showCancelDialog.value = false;
        },
    });
};

const getInitials = (name: string): string => {
    return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
};

const getTagSeverity = (color: string) => {
    const map: Record<string, 'success' | 'info' | 'warn' | 'danger' | 'secondary'> = {
        success: 'success',
        info: 'info',
        warning: 'warn',
        danger: 'danger',
        secondary: 'secondary',
    };
    return map[color] || 'secondary';
};
</script>

<template>
    <DashboardLayout title="Booking Details">
        <div class="booking-page">
            <div class="page-header">
                <Link :href="route('client.bookings.index')" class="back-link">
                    <i class="pi pi-arrow-left"></i>
                    <span>Back to Bookings</span>
                </Link>
            </div>

            <Message v-if="page.props.flash?.success" severity="success" :closable="true" class="flash-message">
                {{ page.props.flash.success }}
            </Message>

            <div class="booking-container">
                <!-- Status Card -->
                <div class="status-card" :class="booking.status">
                    <Tag :severity="getTagSeverity(booking.status_color)" :value="booking.status_label" class="status-tag" />
                    <h2 class="status-title">
                        {{ booking.status === 'pending' ? 'Awaiting Confirmation' :
                           booking.status === 'confirmed' ? 'Booking Confirmed' :
                           booking.status === 'completed' ? 'Service Completed' :
                           booking.status === 'cancelled' ? 'Booking Cancelled' : 'No Show' }}
                    </h2>
                    <p class="status-description">
                        {{ booking.status === 'pending' ? 'The provider will confirm your booking shortly.' :
                           booking.status === 'confirmed' ? 'Your appointment is confirmed. See you there!' :
                           booking.status === 'completed' ? 'Thank you for your visit!' :
                           booking.status === 'cancelled' ? 'This booking has been cancelled.' : '' }}
                    </p>
                </div>

                <!-- Provider Info -->
                <div class="section-card">
                    <h3 class="section-title">Provider</h3>
                    <Link :href="route('provider.public', booking.provider.slug)" class="provider-link">
                        <img
                            v-if="booking.provider.avatar"
                            :src="booking.provider.avatar"
                            :alt="booking.provider.business_name"
                            class="provider-avatar"
                        />
                        <div v-else class="provider-avatar-placeholder">
                            {{ getInitials(booking.provider.business_name) }}
                        </div>
                        <div class="provider-info">
                            <span class="provider-name">{{ booking.provider.business_name }}</span>
                            <span class="provider-location" v-if="booking.provider.location">{{ booking.provider.location }}</span>
                        </div>
                        <i class="pi pi-chevron-right"></i>
                    </Link>
                </div>

                <!-- Appointment Details -->
                <div class="section-card">
                    <h3 class="section-title">Appointment Details</h3>

                    <div class="detail-row">
                        <span class="detail-label">Service</span>
                        <span class="detail-value">{{ booking.service.name }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Date</span>
                        <span class="detail-value">
                            {{ booking.formatted_date }}
                            <Tag v-if="booking.is_today" severity="info" value="Today" class="today-tag" />
                        </span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Time</span>
                        <span class="detail-value">{{ booking.formatted_time }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value">{{ booking.service.duration_minutes }} minutes</span>
                    </div>

                    <div v-if="booking.provider.address" class="detail-row">
                        <span class="detail-label">Location</span>
                        <span class="detail-value">{{ booking.provider.address }}</span>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="section-card" v-if="booking.client_notes || booking.provider_notes || booking.cancellation_reason">
                    <h3 class="section-title">Notes</h3>

                    <div v-if="booking.client_notes" class="note-item">
                        <span class="note-label">Your notes</span>
                        <p class="note-text">{{ booking.client_notes }}</p>
                    </div>

                    <div v-if="booking.provider_notes" class="note-item">
                        <span class="note-label">Provider notes</span>
                        <p class="note-text">{{ booking.provider_notes }}</p>
                    </div>

                    <div v-if="booking.cancellation_reason" class="note-item">
                        <span class="note-label">Cancellation reason</span>
                        <p class="note-text">{{ booking.cancellation_reason }}</p>
                    </div>
                </div>

                <!-- Payment Summary -->
                <div class="section-card">
                    <h3 class="section-title">Payment Summary</h3>

                    <div class="detail-row">
                        <span class="detail-label">Service</span>
                        <span class="detail-value">${{ Number(booking.service_price).toFixed(2) }}</span>
                    </div>

                    <div class="detail-row total">
                        <span class="detail-label">Total</span>
                        <span class="detail-value">{{ booking.total_display }}</span>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="section-card">
                    <h3 class="section-title">Timeline</h3>

                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <span class="timeline-label">Booking created</span>
                                <span class="timeline-time">{{ booking.created_at }}</span>
                            </div>
                        </div>

                        <div v-if="booking.confirmed_at" class="timeline-item">
                            <div class="timeline-dot confirmed"></div>
                            <div class="timeline-content">
                                <span class="timeline-label">Confirmed by provider</span>
                                <span class="timeline-time">{{ booking.confirmed_at }}</span>
                            </div>
                        </div>

                        <div v-if="booking.completed_at" class="timeline-item">
                            <div class="timeline-dot completed"></div>
                            <div class="timeline-content">
                                <span class="timeline-label">Service completed</span>
                                <span class="timeline-time">{{ booking.completed_at }}</span>
                            </div>
                        </div>

                        <div v-if="booking.cancelled_at" class="timeline-item">
                            <div class="timeline-dot cancelled"></div>
                            <div class="timeline-content">
                                <span class="timeline-label">Booking cancelled</span>
                                <span class="timeline-time">{{ booking.cancelled_at }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div v-if="booking.can_cancel" class="actions">
                    <Button
                        label="Cancel Booking"
                        severity="danger"
                        outlined
                        class="w-full"
                        @click="showCancelDialog = true"
                    />
                </div>
            </div>

            <!-- Cancel Dialog -->
            <Dialog
                v-model:visible="showCancelDialog"
                modal
                header="Cancel Booking"
                :style="{ width: '400px' }"
            >
                <p class="cancel-warning">Are you sure you want to cancel this booking?</p>
                <div class="form-group">
                    <label class="form-label">Reason for cancellation</label>
                    <Textarea
                        v-model="cancelForm.reason"
                        placeholder="Please provide a reason..."
                        rows="3"
                        class="w-full"
                        :class="{ 'p-invalid': cancelForm.errors.reason }"
                    />
                    <small v-if="cancelForm.errors.reason" class="p-error">{{ cancelForm.errors.reason }}</small>
                </div>
                <template #footer>
                    <Button label="Keep Booking" severity="secondary" text @click="showCancelDialog = false" />
                    <Button
                        label="Cancel Booking"
                        severity="danger"
                        :loading="cancelForm.processing"
                        @click="cancelBooking"
                    />
                </template>
            </Dialog>
        </div>
    </DashboardLayout>
</template>

<style scoped>
.booking-page {
    max-width: 600px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 1.5rem;
}

.back-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--p-surface-600);
    text-decoration: none;
    font-size: 0.875rem;
    transition: color 0.2s;
}

.back-link:hover {
    color: var(--p-primary-color);
}

.flash-message {
    margin-bottom: 1.5rem;
}

.booking-container {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

/* Status Card */
.status-card {
    padding: 1.5rem;
    border-radius: 16px;
    text-align: center;
    background: linear-gradient(135deg, var(--p-surface-50), var(--p-surface-100));
    border: 1px solid var(--p-surface-200);
}

.status-card.pending {
    background: linear-gradient(135deg, #fef9c3, #fef3c7);
    border-color: #fde047;
}

.status-card.confirmed {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    border-color: #60a5fa;
}

.status-card.completed {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    border-color: #4ade80;
}

.status-card.cancelled {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    border-color: #f87171;
}

.status-tag {
    margin-bottom: 0.75rem;
}

.status-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 0.375rem 0;
}

.status-description {
    font-size: 0.875rem;
    color: var(--p-surface-600);
    margin: 0;
}

/* Section Card */
.section-card {
    background: white;
    border: 1px solid var(--p-surface-200);
    border-radius: 12px;
    padding: 1.25rem;
}

.section-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--p-surface-900);
    margin: 0 0 1rem 0;
}

/* Provider Link */
.provider-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    text-decoration: none;
    color: inherit;
    padding: 0.75rem;
    margin: -0.75rem;
    border-radius: 8px;
    transition: background-color 0.2s;
}

.provider-link:hover {
    background-color: var(--p-surface-50);
}

.provider-avatar {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    object-fit: cover;
}

.provider-avatar-placeholder {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    background: linear-gradient(135deg, var(--p-primary-100), var(--p-primary-200));
    color: var(--p-primary-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.provider-info {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.provider-name {
    font-weight: 600;
    color: var(--p-surface-900);
}

.provider-location {
    font-size: 0.8125rem;
    color: var(--p-surface-500);
}

.provider-link i {
    color: var(--p-surface-400);
}

/* Detail Rows */
.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.625rem 0;
    border-bottom: 1px solid var(--p-surface-100);
}

.detail-row:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.detail-row:first-of-type {
    padding-top: 0;
}

.detail-label {
    font-size: 0.875rem;
    color: var(--p-surface-500);
}

.detail-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-900);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-row.total {
    margin-top: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--p-surface-200);
}

.detail-row.total .detail-label,
.detail-row.total .detail-value {
    font-size: 1rem;
    font-weight: 600;
}

.today-tag {
    font-size: 0.625rem;
}

/* Notes */
.note-item {
    margin-bottom: 1rem;
}

.note-item:last-child {
    margin-bottom: 0;
}

.note-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--p-surface-500);
    text-transform: uppercase;
    margin-bottom: 0.375rem;
}

.note-text {
    font-size: 0.875rem;
    color: var(--p-surface-700);
    margin: 0;
    line-height: 1.5;
}

/* Timeline */
.timeline {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.timeline-item {
    display: flex;
    gap: 0.75rem;
}

.timeline-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: var(--p-surface-300);
    margin-top: 0.25rem;
    flex-shrink: 0;
}

.timeline-dot.confirmed {
    background-color: #3b82f6;
}

.timeline-dot.completed {
    background-color: #22c55e;
}

.timeline-dot.cancelled {
    background-color: #ef4444;
}

.timeline-content {
    display: flex;
    flex-direction: column;
}

.timeline-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-900);
}

.timeline-time {
    font-size: 0.75rem;
    color: var(--p-surface-500);
}

/* Actions */
.actions {
    margin-top: 0.5rem;
}

/* Dialog */
.cancel-warning {
    font-size: 0.9375rem;
    color: var(--p-surface-700);
    margin: 0 0 1rem 0;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--p-surface-700);
}
</style>
