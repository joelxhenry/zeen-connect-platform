<script setup lang="ts">
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import ConsoleLayout from '@/components/layout/ConsoleLayout.vue';
import Button from 'primevue/button';
import Tag from 'primevue/tag';
import Dialog from 'primevue/dialog';
import Textarea from 'primevue/textarea';
import Message from 'primevue/message';

interface BookingData {
    id: number;
    uuid: string;
    client: {
        name: string;
        email: string;
        phone?: string;
        avatar?: string;
    };
    service: {
        name: string;
        description?: string;
        duration_minutes: number;
        price: number;
    };
    booking_date: string;
    formatted_date: string;
    formatted_time: string;
    status: string;
    status_label: string;
    status_color: string;
    service_price: number;
    platform_fee: number;
    total_amount: number;
    total_display: string;
    client_notes?: string;
    provider_notes?: string;
    cancellation_reason?: string;
    is_past: boolean;
    is_today: boolean;
    can_confirm: boolean;
    can_complete: boolean;
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

const statusForm = useForm({
    status: '',
    reason: '',
    provider_notes: '',
});

const confirmBooking = () => {
    statusForm.status = 'confirmed';
    statusForm.put(route('provider.bookings.status', props.booking.uuid));
};

const completeBooking = () => {
    statusForm.status = 'completed';
    statusForm.put(route('provider.bookings.status', props.booking.uuid));
};

const cancelBooking = () => {
    statusForm.status = 'cancelled';
    statusForm.put(route('provider.bookings.status', props.booking.uuid), {
        onSuccess: () => {
            showCancelDialog.value = false;
        },
    });
};

const markNoShow = () => {
    statusForm.status = 'no_show';
    statusForm.put(route('provider.bookings.status', props.booking.uuid));
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

const earnings = props.booking.service_price - props.booking.platform_fee;
</script>

<template>
    <ConsoleLayout title="Booking Details">
        <div class="booking-page">
            <div class="page-header">
                <Link :href="route('provider.bookings.index')" class="back-link">
                    <i class="pi pi-arrow-left"></i>
                    <span>Back to Bookings</span>
                </Link>
            </div>

            <Message v-if="page.props.flash?.success" severity="success" :closable="true" class="flash-message">
                {{ page.props.flash.success }}
            </Message>

            <div class="booking-container">
                <!-- Status & Actions Card -->
                <div class="status-card" :class="booking.status">
                    <div class="status-header">
                        <Tag :severity="getTagSeverity(booking.status_color)" :value="booking.status_label" class="status-tag" />
                        <Tag v-if="booking.is_today" severity="info" value="Today" />
                    </div>

                    <div class="status-actions" v-if="booking.can_confirm || booking.can_complete || booking.can_cancel">
                        <Button
                            v-if="booking.can_confirm"
                            label="Confirm Booking"
                            icon="pi pi-check"
                            @click="confirmBooking"
                            :loading="statusForm.processing && statusForm.status === 'confirmed'"
                        />
                        <Button
                            v-if="booking.can_complete"
                            label="Mark Completed"
                            icon="pi pi-check-circle"
                            severity="success"
                            @click="completeBooking"
                            :loading="statusForm.processing && statusForm.status === 'completed'"
                        />
                        <Button
                            v-if="booking.can_complete"
                            label="No Show"
                            icon="pi pi-user-minus"
                            severity="secondary"
                            outlined
                            @click="markNoShow"
                            :loading="statusForm.processing && statusForm.status === 'no_show'"
                        />
                        <Button
                            v-if="booking.can_cancel"
                            label="Cancel"
                            icon="pi pi-times"
                            severity="danger"
                            outlined
                            @click="showCancelDialog = true"
                        />
                    </div>
                </div>

                <!-- Client Info -->
                <div class="section-card">
                    <h3 class="section-title">Client</h3>
                    <div class="client-info">
                        <img
                            v-if="booking.client.avatar"
                            :src="booking.client.avatar"
                            :alt="booking.client.name"
                            class="client-avatar"
                        />
                        <div v-else class="client-avatar-placeholder">
                            {{ getInitials(booking.client.name) }}
                        </div>
                        <div class="client-details">
                            <span class="client-name">{{ booking.client.name }}</span>
                            <a :href="`mailto:${booking.client.email}`" class="client-contact">
                                <i class="pi pi-envelope"></i>
                                {{ booking.client.email }}
                            </a>
                            <a v-if="booking.client.phone" :href="`tel:${booking.client.phone}`" class="client-contact">
                                <i class="pi pi-phone"></i>
                                {{ booking.client.phone }}
                            </a>
                        </div>
                    </div>
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
                        <span class="detail-value">{{ booking.formatted_date }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Time</span>
                        <span class="detail-value">{{ booking.formatted_time }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Duration</span>
                        <span class="detail-value">{{ booking.service.duration_minutes }} minutes</span>
                    </div>
                </div>

                <!-- Notes Section -->
                <div class="section-card" v-if="booking.client_notes || booking.provider_notes || booking.cancellation_reason">
                    <h3 class="section-title">Notes</h3>

                    <div v-if="booking.client_notes" class="note-item">
                        <span class="note-label">Client notes</span>
                        <p class="note-text">{{ booking.client_notes }}</p>
                    </div>

                    <div v-if="booking.provider_notes" class="note-item">
                        <span class="note-label">Your notes</span>
                        <p class="note-text">{{ booking.provider_notes }}</p>
                    </div>

                    <div v-if="booking.cancellation_reason" class="note-item">
                        <span class="note-label">Cancellation reason</span>
                        <p class="note-text">{{ booking.cancellation_reason }}</p>
                    </div>
                </div>

                <!-- Earnings Summary -->
                <div class="section-card">
                    <h3 class="section-title">Earnings</h3>

                    <div class="detail-row">
                        <span class="detail-label">Service Price</span>
                        <span class="detail-value">${{ Number(booking.service_price).toFixed(2) }}</span>
                    </div>

                    <div class="detail-row">
                        <span class="detail-label">Platform Fee</span>
                        <span class="detail-value fee">-${{ Number(booking.platform_fee).toFixed(2) }}</span>
                    </div>

                    <div class="detail-row earnings">
                        <span class="detail-label">Your Earnings</span>
                        <span class="detail-value">${{ earnings.toFixed(2) }}</span>
                    </div>
                </div>

                <!-- Timeline -->
                <div class="section-card">
                    <h3 class="section-title">Timeline</h3>

                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <span class="timeline-label">Booking received</span>
                                <span class="timeline-time">{{ booking.created_at }}</span>
                            </div>
                        </div>

                        <div v-if="booking.confirmed_at" class="timeline-item">
                            <div class="timeline-dot confirmed"></div>
                            <div class="timeline-content">
                                <span class="timeline-label">You confirmed</span>
                                <span class="timeline-time">{{ booking.confirmed_at }}</span>
                            </div>
                        </div>

                        <div v-if="booking.completed_at && booking.status !== 'no_show'" class="timeline-item">
                            <div class="timeline-dot completed"></div>
                            <div class="timeline-content">
                                <span class="timeline-label">Service completed</span>
                                <span class="timeline-time">{{ booking.completed_at }}</span>
                            </div>
                        </div>

                        <div v-if="booking.status === 'no_show'" class="timeline-item">
                            <div class="timeline-dot no-show"></div>
                            <div class="timeline-content">
                                <span class="timeline-label">Marked as no show</span>
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
            </div>

            <!-- Cancel Dialog -->
            <Dialog
                v-model:visible="showCancelDialog"
                modal
                header="Cancel Booking"
                :style="{ width: '400px' }"
            >
                <p class="cancel-warning">Are you sure you want to cancel this booking? The client will be notified.</p>
                <div class="form-group">
                    <label class="form-label">Reason for cancellation</label>
                    <Textarea
                        v-model="statusForm.reason"
                        placeholder="Please provide a reason..."
                        rows="3"
                        class="w-full"
                        :class="{ 'p-invalid': statusForm.errors.reason }"
                    />
                    <small v-if="statusForm.errors.reason" class="p-error">{{ statusForm.errors.reason }}</small>
                </div>
                <template #footer>
                    <Button label="Keep Booking" severity="secondary" text @click="showCancelDialog = false" />
                    <Button
                        label="Cancel Booking"
                        severity="danger"
                        :loading="statusForm.processing"
                        @click="cancelBooking"
                    />
                </template>
            </Dialog>
        </div>
    </ConsoleLayout>
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
    padding: 1.25rem;
    border-radius: 12px;
    background: white;
    border: 1px solid var(--p-surface-200);
}

.status-card.pending {
    border-color: #fde047;
    background: linear-gradient(135deg, white, #fefce8);
}

.status-card.confirmed {
    border-color: #60a5fa;
    background: linear-gradient(135deg, white, #eff6ff);
}

.status-card.completed {
    border-color: #4ade80;
    background: linear-gradient(135deg, white, #f0fdf4);
}

.status-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.status-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
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

/* Client Info */
.client-info {
    display: flex;
    gap: 0.75rem;
}

.client-avatar {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    object-fit: cover;
}

.client-avatar-placeholder {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--p-surface-100), var(--p-surface-200));
    color: var(--p-surface-600);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 1rem;
}

.client-details {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.client-name {
    font-weight: 600;
    color: var(--p-surface-900);
}

.client-contact {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.8125rem;
    color: var(--p-surface-600);
    text-decoration: none;
}

.client-contact:hover {
    color: var(--p-primary-color);
}

.client-contact i {
    font-size: 0.75rem;
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
}

.detail-value.fee {
    color: var(--p-red-500);
}

.detail-row.earnings {
    margin-top: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--p-surface-200);
}

.detail-row.earnings .detail-label,
.detail-row.earnings .detail-value {
    font-size: 1rem;
    font-weight: 600;
}

.detail-row.earnings .detail-value {
    color: var(--p-green-600);
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

.timeline-dot.no-show {
    background-color: #6b7280;
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
