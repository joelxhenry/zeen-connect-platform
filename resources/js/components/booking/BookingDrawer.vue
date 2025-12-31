<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import Drawer from 'primevue/drawer';
import Avatar from 'primevue/avatar';
import Tag from 'primevue/tag';
import { ConsoleButton } from '@/components/console';
import providerRoutes from '@/routes/provider';

interface Booking {
    uuid: string;
    client: {
        name: string;
        email?: string;
        phone?: string;
        avatar?: string;
    };
    service: {
        id: number;
        name: string;
        duration?: number;
        category?: string;
    };
    team_member?: {
        id: number;
        name: string;
    } | null;
    booking_date: string;
    date: string;
    date_short: string;
    time: string;
    end_time: string;
    total_amount: string;
    service_price?: number;
    deposit_amount?: number;
    deposit_paid?: boolean;
    status: string;
    status_label: string;
    status_color: string;
    provider_notes?: string;
    client_notes?: string;
    created_at: string;
}

interface Props {
    booking: Booking | null;
    visible: boolean;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:visible': [value: boolean];
    'action': [action: string, bookingUuid: string];
}>();

const drawerVisible = computed({
    get: () => props.visible,
    set: (value) => emit('update:visible', value),
});

const getInitials = (name: string) => {
    return name
        .split(' ')
        .map(n => n[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

const getStatusSeverity = (status: string): "success" | "info" | "warn" | "danger" | "secondary" | "contrast" | undefined => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'success';
        case 'completed': return 'info';
        case 'cancelled': return 'danger';
        case 'no_show': return 'secondary';
        default: return 'secondary';
    }
};

const canConfirm = computed(() => props.booking?.status === 'pending');
const canComplete = computed(() => props.booking?.status === 'confirmed');
const canCancel = computed(() => ['pending', 'confirmed'].includes(props.booking?.status || ''));
const canMarkNoShow = computed(() => props.booking?.status === 'confirmed');

const updateBookingStatus = (action: string) => {
    if (!props.booking) return;

    const uuid = props.booking.uuid;
    const routeMap: Record<string, string> = {
        confirm: providerRoutes.bookings.confirm.url(uuid),
        complete: providerRoutes.bookings.complete.url(uuid),
        cancel: providerRoutes.bookings.cancel.url(uuid),
        noShow: providerRoutes.bookings.noShow.url(uuid),
    };

    const url = routeMap[action];
    if (url) {
        router.post(url, {}, {
            preserveScroll: true,
            onSuccess: () => {
                emit('update:visible', false);
            },
        });
    }
};
</script>

<template>
    <Drawer
        v-model:visible="drawerVisible"
        position="right"
        :header="booking ? 'Booking Details' : ''"
        class="booking-drawer"
        :style="{ width: '400px' }"
    >
        <template v-if="booking">
            <div class="drawer-content">
                <!-- Client Section -->
                <div class="section client-section">
                    <div class="client-header">
                        <Avatar
                            v-if="booking.client.avatar"
                            :image="booking.client.avatar"
                            shape="circle"
                            size="large"
                            class="client-avatar"
                        />
                        <Avatar
                            v-else
                            :label="getInitials(booking.client.name)"
                            shape="circle"
                            size="large"
                            class="client-avatar client-avatar-fallback"
                        />
                        <div class="client-info">
                            <h3 class="client-name">{{ booking.client.name }}</h3>
                            <Tag
                                :severity="getStatusSeverity(booking.status)"
                                :value="booking.status_label"
                                rounded
                            />
                        </div>
                    </div>
                    <div class="client-contact">
                        <div v-if="booking.client.email" class="contact-item">
                            <i class="pi pi-envelope"></i>
                            <a :href="`mailto:${booking.client.email}`">{{ booking.client.email }}</a>
                        </div>
                        <div v-if="booking.client.phone" class="contact-item">
                            <i class="pi pi-phone"></i>
                            <a :href="`tel:${booking.client.phone}`">{{ booking.client.phone }}</a>
                        </div>
                    </div>
                </div>

                <!-- Service Section -->
                <div class="section">
                    <h4 class="section-title">Service</h4>
                    <div class="detail-card">
                        <div class="detail-row">
                            <span class="detail-label">Service</span>
                            <span class="detail-value">{{ booking.service.name }}</span>
                        </div>
                        <div v-if="booking.service.category" class="detail-row">
                            <span class="detail-label">Category</span>
                            <span class="detail-value">{{ booking.service.category }}</span>
                        </div>
                        <div v-if="booking.service.duration" class="detail-row">
                            <span class="detail-label">Duration</span>
                            <span class="detail-value">{{ booking.service.duration }} min</span>
                        </div>
                        <div v-if="booking.team_member" class="detail-row">
                            <span class="detail-label">Team Member</span>
                            <span class="detail-value">{{ booking.team_member.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Date & Time Section -->
                <div class="section">
                    <h4 class="section-title">Date & Time</h4>
                    <div class="detail-card">
                        <div class="detail-row">
                            <span class="detail-label">Date</span>
                            <span class="detail-value">{{ booking.date }}</span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Time</span>
                            <span class="detail-value">{{ booking.time }} - {{ booking.end_time }}</span>
                        </div>
                    </div>
                </div>

                <!-- Payment Section -->
                <div class="section">
                    <h4 class="section-title">Payment</h4>
                    <div class="detail-card">
                        <div class="detail-row">
                            <span class="detail-label">Total Amount</span>
                            <span class="detail-value total-amount">{{ booking.total_amount }}</span>
                        </div>
                        <div v-if="booking.deposit_amount" class="detail-row">
                            <span class="detail-label">Deposit</span>
                            <span class="detail-value">
                                ${{ booking.deposit_amount }}
                                <Tag
                                    :severity="booking.deposit_paid ? 'success' : 'warn'"
                                    :value="booking.deposit_paid ? 'Paid' : 'Pending'"
                                    rounded
                                    class="deposit-tag"
                                />
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Notes Section -->
                <div v-if="booking.client_notes || booking.provider_notes" class="section">
                    <h4 class="section-title">Notes</h4>
                    <div class="detail-card">
                        <div v-if="booking.client_notes" class="note-item">
                            <span class="note-label">Client Notes</span>
                            <p class="note-text">{{ booking.client_notes }}</p>
                        </div>
                        <div v-if="booking.provider_notes" class="note-item">
                            <span class="note-label">Your Notes</span>
                            <p class="note-text">{{ booking.provider_notes }}</p>
                        </div>
                    </div>
                </div>

                <!-- Booking Info -->
                <div class="section">
                    <p class="booking-meta">Booked on {{ booking.created_at }}</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="drawer-footer">
                <ConsoleButton
                    v-if="canConfirm"
                    label="Confirm Booking"
                    icon="pi pi-check"
                    variant="primary"
                    @click="updateBookingStatus('confirm')"
                />
                <ConsoleButton
                    v-if="canComplete"
                    label="Mark Complete"
                    icon="pi pi-check-circle"
                    variant="primary"
                    @click="updateBookingStatus('complete')"
                />
                <ConsoleButton
                    v-if="canMarkNoShow"
                    label="Mark No-Show"
                    icon="pi pi-user-minus"
                    variant="secondary"
                    @click="updateBookingStatus('noShow')"
                />
                <ConsoleButton
                    v-if="canCancel"
                    label="Cancel Booking"
                    icon="pi pi-times"
                    variant="danger"
                    @click="updateBookingStatus('cancel')"
                />
            </div>
        </template>
    </Drawer>
</template>

<style scoped>
.drawer-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding-bottom: 1rem;
}

.section {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.section-title {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--color-slate-500, #64748b);
}

/* Client Section */
.client-section {
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--color-slate-100, #f1f5f9);
}

.client-header {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.client-avatar {
    width: 56px !important;
    height: 56px !important;
    flex-shrink: 0;
}

.client-avatar-fallback {
    background-color: #106B4F !important;
    color: white !important;
    font-size: 1.125rem !important;
}

.client-info {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.client-name {
    margin: 0;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-slate-900, #0f172a);
}

.client-contact {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: 0.75rem;
}

.contact-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--color-slate-600, #475569);
}

.contact-item i {
    color: var(--color-slate-400, #94a3b8);
    width: 1rem;
}

.contact-item a {
    color: #106B4F;
    text-decoration: none;
}

.contact-item a:hover {
    text-decoration: underline;
}

/* Detail Card */
.detail-card {
    background-color: var(--color-slate-50, #f8fafc);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.detail-label {
    font-size: 0.875rem;
    color: var(--color-slate-500, #64748b);
}

.detail-value {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-slate-900, #0f172a);
    text-align: right;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.total-amount {
    font-size: 1rem;
    font-weight: 600;
    color: #106B4F;
}

.deposit-tag {
    font-size: 0.625rem;
}

/* Notes */
.note-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.note-item + .note-item {
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--color-slate-200, #e2e8f0);
}

.note-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--color-slate-500, #64748b);
}

.note-text {
    margin: 0;
    font-size: 0.875rem;
    color: var(--color-slate-700, #334155);
    line-height: 1.5;
}

/* Meta */
.booking-meta {
    margin: 0;
    font-size: 0.75rem;
    color: var(--color-slate-400, #94a3b8);
    text-align: center;
}

/* Footer */
.drawer-footer {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--color-slate-100, #f1f5f9);
    margin-top: auto;
}
</style>
