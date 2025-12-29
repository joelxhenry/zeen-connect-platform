<?php

namespace App\Domains\Booking\Services;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Provider\Models\Provider;
use App\Domains\Provider\Models\TeamMember;
use App\Domains\Service\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Get available dates for a provider/team member within a date range.
     */
    public function getAvailableDates(
        Provider $provider,
        string $startDate,
        string $endDate,
        ?TeamMember $teamMember = null
    ): array {
        $availableDates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // If team member specified, use their availability
        if ($teamMember) {
            while ($current->lte($end)) {
                if ($teamMember->isAvailableOnDate($current)) {
                    $availableDates[] = $current->format('Y-m-d');
                }
                $current->addDay();
            }

            return $availableDates;
        }

        // Otherwise, use provider availability
        $weeklyAvailability = $provider->availability()
            ->where('is_available', true)
            ->get()
            ->keyBy('day_of_week');

        // Get blocked dates in the range
        $blockedDates = $provider->blockedDates()
            ->whereBetween('date', [$startDate, $endDate])
            ->pluck('date')
            ->map(fn ($date) => $date->format('Y-m-d'))
            ->toArray();

        while ($current->lte($end)) {
            $dayOfWeek = $current->dayOfWeek;
            $dateString = $current->format('Y-m-d');

            // Check if provider works on this day and it's not blocked
            if ($weeklyAvailability->has($dayOfWeek) && ! in_array($dateString, $blockedDates)) {
                $availableDates[] = $dateString;
            }

            $current->addDay();
        }

        return $availableDates;
    }

    /**
     * Get available time slots for a specific date and service.
     */
    public function getAvailableSlots(
        Provider $provider,
        Service $service,
        string $date,
        ?TeamMember $teamMember = null
    ): array {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek;
        $bufferMinutes = $service->getEffectiveBufferMinutes();

        // Get availability schedule
        if ($teamMember) {
            $scheduleData = $teamMember->getEffectiveAvailabilityForDay($dayOfWeek);
            if (! $scheduleData || ! $scheduleData['is_available']) {
                return [];
            }
            $startTime = $scheduleData['start_time'];
            $endTime = $scheduleData['end_time'];

            // Check blocked dates
            if ($teamMember->provider->blockedDates()->where('date', $date)->exists()) {
                return [];
            }
            if ($teamMember->blockedDates()->where('date', $date)->exists()) {
                return [];
            }

            // Get breaks for the team member (includes provider breaks)
            $breaks = $teamMember->getBreaksForDay($dayOfWeek);
        } else {
            $availability = $provider->availability()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_available', true)
                ->first();

            if (! $availability) {
                return [];
            }

            $startTime = $availability->start_time;
            $endTime = $availability->end_time;

            // Check if date is blocked
            if ($provider->blockedDates()->where('date', $date)->exists()) {
                return [];
            }

            // Get provider breaks
            $breaks = $provider->breaks()->forDay($dayOfWeek)->get();
        }

        // Get existing bookings for this date
        $bookingsQuery = Booking::where('provider_id', $provider->id)
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED]);

        // If team member is specified, only consider their bookings for conflict checking
        if ($teamMember) {
            $bookingsQuery->where('team_member_id', $teamMember->id);
        }

        $existingBookings = $bookingsQuery->get(['start_time', 'end_time']);

        // Generate time slots
        $slots = $this->generateTimeSlots(
            $startTime,
            $endTime,
            $service->duration_minutes,
            $existingBookings,
            $date,
            $breaks,
            $bufferMinutes
        );

        return $slots;
    }

    /**
     * Generate available time slots based on working hours, existing bookings, breaks, and buffer.
     */
    protected function generateTimeSlots(
        string $startTime,
        string $endTime,
        int $durationMinutes,
        Collection $existingBookings,
        string $date,
        Collection $breaks = null,
        int $bufferMinutes = 0
    ): array {
        $slots = [];
        $slotInterval = 30; // 30-minute slot intervals
        $breaks = $breaks ?? collect();

        $current = Carbon::parse($date.' '.$startTime);
        $dayEnd = Carbon::parse($date.' '.$endTime);
        $now = Carbon::now();

        while ($current->copy()->addMinutes($durationMinutes)->lte($dayEnd)) {
            $slotEnd = $current->copy()->addMinutes($durationMinutes);

            // Skip slots in the past
            if ($current->lt($now)) {
                $current->addMinutes($slotInterval);
                continue;
            }

            // Check if slot conflicts with existing bookings (including buffer)
            $hasBookingConflict = $this->hasConflictWithBuffer(
                $current,
                $slotEnd,
                $existingBookings,
                $bufferMinutes,
                $date
            );

            // Check if slot conflicts with breaks
            $hasBreakConflict = $this->conflictsWithBreaks(
                $current,
                $slotEnd,
                $breaks,
                $date
            );

            if (! $hasBookingConflict && ! $hasBreakConflict) {
                $slots[] = [
                    'start_time' => $current->format('H:i'),
                    'end_time' => $slotEnd->format('H:i'),
                    'display' => $current->format('g:i A').' - '.$slotEnd->format('g:i A'),
                ];
            }

            $current->addMinutes($slotInterval);
        }

        return $slots;
    }

    /**
     * Check if a time slot conflicts with existing bookings (considering buffer time).
     */
    protected function hasConflictWithBuffer(
        Carbon $start,
        Carbon $end,
        Collection $bookings,
        int $bufferMinutes,
        string $date
    ): bool {
        foreach ($bookings as $booking) {
            $bookingStart = Carbon::parse($date.' '.$booking->start_time);
            $bookingEnd = Carbon::parse($date.' '.$booking->end_time);

            // Add buffer time to existing booking
            $bufferedBookingStart = $bookingStart->copy()->subMinutes($bufferMinutes);
            $bufferedBookingEnd = $bookingEnd->copy()->addMinutes($bufferMinutes);

            // Check for overlap with buffered booking window
            if ($start->lt($bufferedBookingEnd) && $end->gt($bufferedBookingStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a time slot conflicts with any breaks.
     */
    protected function conflictsWithBreaks(
        Carbon $start,
        Carbon $end,
        Collection $breaks,
        string $date
    ): bool {
        foreach ($breaks as $break) {
            $breakStart = Carbon::parse($date.' '.$break->start_time);
            $breakEnd = Carbon::parse($date.' '.$break->end_time);

            // Check for overlap
            if ($start->lt($breakEnd) && $end->gt($breakStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a time slot conflicts with existing bookings (legacy method without buffer).
     */
    protected function hasConflict(Carbon $start, Carbon $end, Collection $bookings): bool
    {
        return $this->hasConflictWithBuffer($start, $end, $bookings, 0, $start->format('Y-m-d'));
    }

    /**
     * Check if a specific slot is still available.
     */
    public function isSlotAvailable(
        Provider $provider,
        string $date,
        string $startTime,
        string $endTime,
        ?TeamMember $teamMember = null,
        int $bufferMinutes = 0
    ): bool {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek;

        // Validate schedule
        if ($teamMember) {
            $scheduleData = $teamMember->getEffectiveAvailabilityForDay($dayOfWeek);
            if (! $scheduleData || ! $scheduleData['is_available']) {
                return false;
            }

            $scheduleStart = $scheduleData['start_time'];
            $scheduleEnd = $scheduleData['end_time'];

            // Check blocked dates
            if ($teamMember->provider->blockedDates()->where('date', $date)->exists()) {
                return false;
            }
            if ($teamMember->blockedDates()->where('date', $date)->exists()) {
                return false;
            }

            // Get breaks
            $breaks = $teamMember->getBreaksForDay($dayOfWeek);
        } else {
            $availability = $provider->availability()
                ->where('day_of_week', $dayOfWeek)
                ->where('is_available', true)
                ->first();

            if (! $availability) {
                return false;
            }

            $scheduleStart = $availability->start_time;
            $scheduleEnd = $availability->end_time;

            // Check date is not blocked
            if ($provider->blockedDates()->where('date', $date)->exists()) {
                return false;
            }

            // Get breaks
            $breaks = $provider->breaks()->forDay($dayOfWeek)->get();
        }

        // Check the slot is within working hours
        if ($startTime < $scheduleStart || $endTime > $scheduleEnd) {
            return false;
        }

        // Check for conflicts with breaks
        $slotStart = Carbon::parse($date.' '.$startTime);
        $slotEnd = Carbon::parse($date.' '.$endTime);

        if ($this->conflictsWithBreaks($slotStart, $slotEnd, $breaks, $date)) {
            return false;
        }

        // Check for conflicting bookings
        $bookingsQuery = Booking::where('provider_id', $provider->id)
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED]);

        if ($teamMember) {
            $bookingsQuery->where('team_member_id', $teamMember->id);
        }

        $existingBookings = $bookingsQuery->get(['start_time', 'end_time']);

        return ! $this->hasConflictWithBuffer(
            $slotStart,
            $slotEnd,
            $existingBookings,
            $bufferMinutes,
            $date
        );
    }

    /**
     * Calculate pricing for a booking.
     */
    public function calculatePricing(Service $service, float $commissionRate = 0.15): array
    {
        $servicePrice = (float) $service->price;
        $platformFee = round($servicePrice * $commissionRate, 2);
        $totalAmount = $servicePrice;

        return [
            'service_price' => $servicePrice,
            'platform_fee' => $platformFee,
            'total_amount' => $totalAmount,
            'provider_payout' => $servicePrice - $platformFee,
        ];
    }
}
