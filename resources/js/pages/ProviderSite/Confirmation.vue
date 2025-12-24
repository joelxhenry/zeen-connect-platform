<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import ProviderSiteLayout from '@/components/layout/ProviderSiteLayout.vue';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';
import Tag from 'primevue/tag';

interface Props {
    booking: {
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
        is_guest_booking: boolean;
        client_name: string;
        client_email: string;
        requires_deposit: boolean;
        deposit_amount: number;
        deposit_paid: boolean;
        balance_amount: number;
        can_pay: boolean;
    };
}

const props = defineProps<Props>();
const page = usePage();

const getStatusSeverity = (status: string) => {
    switch (status) {
        case 'pending': return 'warn';
        case 'confirmed': return 'success';
        case 'completed': return 'info';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
};

const getInitials = (name: string) => {
    return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
};
</script>

<template>
    <ProviderSiteLayout title="Booking Confirmation">
        <div class="confirmation-page">
            <div class="max-w-2xl mx-auto px-4 py-8">
                <!-- Success Header -->
                <div class="text-center mb-6">
                    <div class="w-16 h-16 rounded-full bg-[#106B4F]/10 flex items-center justify-center mx-auto mb-4">
                        <i class="pi pi-check text-3xl text-[#106B4F]"></i>
                    </div>
                    <h1 class="text-2xl font-semibold text-[#0D1F1B] m-0">Booking Submitted!</h1>
                    <p class="text-gray-500 mt-2">
                        {{ booking.requires_deposit && !booking.deposit_paid
                            ? 'Complete your deposit payment to secure your booking.'
                            : 'We\'ve sent a confirmation email to ' + booking.client_email
                        }}
                    </p>
                </div>

                <!-- Booking Details Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Booking Details</h2>
                        <Tag :value="booking.status_label" :severity="getStatusSeverity(booking.status)" />
                    </div>
                    <div class="p-5 space-y-4">
                        <!-- Provider Info -->
                        <div class="flex items-center gap-4 pb-4 border-b border-gray-100">
                            <Avatar
                                v-if="booking.provider.avatar"
                                :image="booking.provider.avatar"
                                shape="circle"
                                class="!w-12 !h-12"
                            />
                            <Avatar
                                v-else
                                :label="getInitials(booking.provider.business_name)"
                                shape="circle"
                                class="!w-12 !h-12 !bg-[#106B4F]"
                            />
                            <div>
                                <h3 class="font-medium text-[#0D1F1B] m-0">{{ booking.provider.business_name }}</h3>
                                <p v-if="booking.provider.location" class="text-sm text-gray-500 m-0">
                                    <i class="pi pi-map-marker mr-1"></i>
                                    {{ booking.provider.location }}
                                </p>
                            </div>
                        </div>

                        <!-- Service & Time -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm text-gray-500 block mb-1">Service</label>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ booking.service.name }}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500 block mb-1">Duration</label>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ booking.service.duration_minutes }} minutes</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500 block mb-1">Date</label>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ booking.formatted_date }}</p>
                            </div>
                            <div>
                                <label class="text-sm text-gray-500 block mb-1">Time</label>
                                <p class="font-medium text-[#0D1F1B] m-0">{{ booking.formatted_time }}</p>
                            </div>
                        </div>

                        <!-- Your Info -->
                        <div class="pt-4 border-t border-gray-100">
                            <label class="text-sm text-gray-500 block mb-1">Booked by</label>
                            <p class="font-medium text-[#0D1F1B] m-0">{{ booking.client_name }}</p>
                            <p class="text-sm text-gray-500 m-0">{{ booking.client_email }}</p>
                        </div>
                    </div>
                </div>

                <!-- Payment Card (if deposit required) -->
                <div v-if="booking.requires_deposit" class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-[#0D1F1B] m-0">Payment</h2>
                    </div>
                    <div class="p-5">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Service Total</span>
                                <span class="font-medium">${{ booking.service_price.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-[#106B4F]">
                                <span>Deposit Required</span>
                                <span class="font-medium">${{ booking.deposit_amount.toFixed(2) }}</span>
                            </div>
                            <div class="flex justify-between text-gray-500">
                                <span>Balance Due at Appointment</span>
                                <span>${{ booking.balance_amount.toFixed(2) }}</span>
                            </div>
                        </div>

                        <hr class="my-4 border-gray-200" />

                        <div v-if="booking.deposit_paid" class="flex items-center gap-2 p-3 bg-green-50 rounded-lg text-green-700">
                            <i class="pi pi-check-circle"></i>
                            <span class="text-sm font-medium">Deposit Paid</span>
                        </div>
                        <div v-else>
                            <Link :href="`/payment/${booking.uuid}/checkout`">
                                <Button
                                    label="Pay Deposit Now"
                                    icon="pi pi-credit-card"
                                    class="w-full !bg-[#106B4F] !border-[#106B4F]"
                                />
                            </Link>
                            <p class="text-xs text-center text-gray-400 mt-2 m-0">
                                Pay your deposit to secure your appointment
                            </p>
                        </div>
                    </div>
                </div>

                <!-- What's Next Card -->
                <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-200">
                        <h2 class="text-base font-semibold text-[#0D1F1B] m-0">What's Next?</h2>
                    </div>
                    <div class="p-5">
                        <ul class="space-y-3 m-0 p-0 list-none">
                            <li v-if="booking.requires_deposit && !booking.deposit_paid" class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-yellow-100 text-yellow-600 flex items-center justify-center shrink-0 text-sm">1</span>
                                <div>
                                    <p class="font-medium text-[#0D1F1B] m-0">Complete your deposit payment</p>
                                    <p class="text-sm text-gray-500 m-0">Pay the deposit to secure your booking</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-[#106B4F]/10 text-[#106B4F] flex items-center justify-center shrink-0 text-sm">
                                    {{ booking.requires_deposit && !booking.deposit_paid ? '2' : '1' }}
                                </span>
                                <div>
                                    <p class="font-medium text-[#0D1F1B] m-0">Check your email</p>
                                    <p class="text-sm text-gray-500 m-0">We've sent booking details to {{ booking.client_email }}</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-[#106B4F]/10 text-[#106B4F] flex items-center justify-center shrink-0 text-sm">
                                    {{ booking.requires_deposit && !booking.deposit_paid ? '3' : '2' }}
                                </span>
                                <div>
                                    <p class="font-medium text-[#0D1F1B] m-0">Save the date</p>
                                    <p class="text-sm text-gray-500 m-0">{{ booking.formatted_date }} at {{ booking.formatted_time }}</p>
                                </div>
                            </li>
                            <li class="flex items-start gap-3">
                                <span class="w-6 h-6 rounded-full bg-[#106B4F]/10 text-[#106B4F] flex items-center justify-center shrink-0 text-sm">
                                    {{ booking.requires_deposit && !booking.deposit_paid ? '4' : '3' }}
                                </span>
                                <div>
                                    <p class="font-medium text-[#0D1F1B] m-0">Arrive on time</p>
                                    <p class="text-sm text-gray-500 m-0">{{ booking.provider.address || booking.provider.location }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-center gap-4 mt-6">
                    <Link href="/">
                        <Button label="Back to Home" severity="secondary" />
                    </Link>
                    <Link href="/services">
                        <Button label="Browse Services" outlined class="!border-[#106B4F] !text-[#106B4F]" />
                    </Link>
                </div>
            </div>
        </div>
    </ProviderSiteLayout>
</template>

<style scoped>
.confirmation-page {
    min-height: 100%;
    background-color: #f9fafb;
}
</style>
