import { ref, computed, watch, onMounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useToast } from 'primevue/usetoast';
import { useApi } from '@/composables/useApi';
import { ApiError } from '@/types/api';
import type {
    BookingPageProps,
    ServiceForBooking,
    TeamMemberForBooking,
    Slot,
} from '@/types/providersite';

interface SlotsResponse {
    slots: Slot[];
}

/**
 * Parse date query parameter from URL.
 * Expects format: YYYY-MM-DD
 */
const getDateFromQuery = (): Date | null => {
    if (typeof window === 'undefined') return null;

    const params = new URLSearchParams(window.location.search);
    const dateStr = params.get('date');

    if (!dateStr) return null;

    // Validate format YYYY-MM-DD
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!dateRegex.test(dateStr)) return null;

    // Parse the date (add time to avoid timezone issues)
    const [year, month, day] = dateStr.split('-').map(Number);
    const date = new Date(year, month - 1, day);

    // Validate the date is real (e.g., not Feb 31)
    if (isNaN(date.getTime())) return null;
    if (date.getFullYear() !== year || date.getMonth() !== month - 1 || date.getDate() !== day) {
        return null;
    }

    return date;
};

/**
 * Composable for provider site booking page logic.
 *
 * Handles all the complex reactive state, watchers, API calls, and form
 * submission for the booking flow.
 */
export function useProviderSiteBooking(props: BookingPageProps) {
    const toast = useToast();
    const api = useApi({ showErrorToast: false });

    // Find preselected service
    const preselectedServiceData = props.preselectedService
        ? props.services.find(s => s.id === props.preselectedService) || null
        : null;

    // Check for preselected date from query params
    const preselectedDate = getDateFromQuery();

    // Form state
    const selectedService = ref<ServiceForBooking | null>(preselectedServiceData);
    const selectedTeamMember = ref<TeamMemberForBooking | null>(null);
    const selectedDate = ref<Date | null>(null);
    const selectedSlot = ref<Slot | null>(null);
    const availableSlots = ref<Slot[]>([]);
    const loadingSlots = ref(false);
    const isInitialized = ref(false);

    // Inertia form for submission
    const form = useForm({
        provider_id: props.provider.id,
        service_id: preselectedServiceData?.id ?? null,
        team_member_id: null as number | null,
        date: '',
        start_time: '',
        notes: '',
        guest_email: '',
        guest_name: props.user?.name || '',
        guest_phone: props.user?.phone || '',
    });

    // Computed properties
    const hasTeamMembers = computed(() => props.teamMembers?.length > 0);
    const currentFees = computed(() => selectedService.value?.fees || null);
    const availableDatesSet = computed(() => new Set(props.availableDates));

    const minDate = computed(() => new Date());
    const maxDate = computed(() => {
        const date = new Date();
        date.setDate(date.getDate() + 30);
        return date;
    });

    /**
     * Format date to YYYY-MM-DD in local timezone (avoids UTC shift issues).
     */
    const formatDateLocal = (date: Date): string => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    /**
     * Generate disabled dates for the calendar (dates NOT in availableDates).
     */
    const disabledDates = computed(() => {
        const disabled: Date[] = [];
        const current = new Date(minDate.value);
        const end = new Date(maxDate.value);

        while (current <= end) {
            const dateStr = formatDateLocal(current);
            if (!availableDatesSet.value.has(dateStr)) {
                disabled.push(new Date(current));
            }
            current.setDate(current.getDate() + 1);
        }

        return disabled;
    });

    /**
     * Check if form can be submitted.
     */
    const canSubmit = computed(() => {
        if (!selectedService.value || !selectedDate.value || !selectedSlot.value) {
            return false;
        }
        if (!props.isAuthenticated) {
            return !!form.guest_email && !!form.guest_name && !!form.guest_phone;
        }
        return true;
    });

    /**
     * Check if a date is available for booking.
     */
    const isDateAvailable = (date: Date): boolean => {
        const dateStr = formatDateLocal(date);
        return availableDatesSet.value.has(dateStr);
    };

    /**
     * Initialize preselected date if available.
     * Should be called after component mounts.
     */
    const initializePreselectedDate = () => {
        if (isInitialized.value) return;
        isInitialized.value = true;

        // If we have a preselected date and service, and the date is available
        if (preselectedDate && selectedService.value && isDateAvailable(preselectedDate)) {
            selectedDate.value = preselectedDate;
        }
    };

    // Watchers for cascading updates

    // When service changes, reset everything downstream (except on initial load with preselected date)
    watch(selectedService, (service, oldService) => {
        form.service_id = service?.id ?? null;
        selectedTeamMember.value = null;
        selectedSlot.value = null;
        availableSlots.value = [];

        // Only reset date if this isn't the initial service selection with a preselected date
        if (oldService !== null || !preselectedDate || !service) {
            selectedDate.value = null;
        } else if (preselectedDate && service && isDateAvailable(preselectedDate)) {
            // Initial load with preselected date - set it
            selectedDate.value = preselectedDate;
        }
    });

    // When team member changes, refetch slots if date is selected
    watch(selectedTeamMember, (member) => {
        form.team_member_id = member?.id ?? null;
        if (selectedDate.value && selectedService.value) {
            fetchSlots();
        }
    });

    // When date changes, fetch available slots
    watch(selectedDate, async (date) => {
        if (!date || !selectedService.value) {
            availableSlots.value = [];
            selectedSlot.value = null;
            return;
        }

        form.date = formatDateLocal(date);
        await fetchSlots();
    });

    // When slot changes, update form
    watch(selectedSlot, (slot) => {
        form.start_time = slot?.start_time || '';
    });

    /**
     * Fetch available time slots for the selected date and service.
     */
    const fetchSlots = async () => {
        if (!selectedDate.value || !selectedService.value) return;

        loadingSlots.value = true;
        try {
            const params: Record<string, unknown> = {
                service_id: selectedService.value.id,
                date: form.date,
            };

            if (selectedTeamMember.value) {
                params.team_member_id = selectedTeamMember.value.id;
            }

            const result = await api.get<SlotsResponse>('/book/slots', { params });
            availableSlots.value = result.slots || [];
            selectedSlot.value = null;
        } catch (e) {
            const message = e instanceof ApiError ? e.message : 'Failed to load available time slots';
            toast.add({
                severity: 'error',
                summary: 'Error',
                detail: message,
                life: 3000,
            });
        } finally {
            loadingSlots.value = false;
        }
    };

    /**
     * Submit the booking form.
     */
    const submit = (options?: Record<string, unknown>) => {
        form.post('/book', {
            preserveScroll: true,
            onSuccess: () => {
                toast.add({
                    severity: 'success',
                    summary: 'Success',
                    detail: 'Booking created successfully!',
                    life: 3000,
                });
            },
            onError: () => {
                toast.add({
                    severity: 'error',
                    summary: 'Error',
                    detail: 'Failed to create booking. Please try again.',
                    life: 3000,
                });
            },
            ...(options ?? {}),
        });
    };

    // Initialize preselected date on mount if service is already selected
    onMounted(() => {
        if (preselectedDate && selectedService.value && isDateAvailable(preselectedDate)) {
            selectedDate.value = preselectedDate;
        }
    });

    return {
        // State
        selectedService,
        selectedTeamMember,
        selectedDate,
        selectedSlot,
        availableSlots,
        loadingSlots,
        form,

        // Computed
        hasTeamMembers,
        currentFees,
        minDate,
        maxDate,
        disabledDates,
        canSubmit,

        // Methods
        fetchSlots,
        submit,
    };
}
