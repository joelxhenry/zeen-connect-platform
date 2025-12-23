<?php

namespace App\Domains\Booking\Services;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Domains\Provider\Models\Provider;
use App\Domains\Service\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    /**
     * Get available dates for a provider within a date range.
     */
    public function getAvailableDates(Provider $provider, string $startDate, string $endDate): array
    {
        $availableDates = [];
        $current = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);

        // Get provider's weekly availability
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
    public function getAvailableSlots(Provider $provider, Service $service, string $date): array
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek;

        // Get provider's availability for this day
        $availability = $provider->availability()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->first();

        if (! $availability) {
            return [];
        }

        // Check if date is blocked
        $isBlocked = $provider->blockedDates()
            ->where('date', $date)
            ->exists();

        if ($isBlocked) {
            return [];
        }

        // Get existing bookings for this date
        $existingBookings = Booking::where('provider_id', $provider->id)
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->get(['start_time', 'end_time']);

        // Generate time slots
        $slots = $this->generateTimeSlots(
            $availability->start_time,
            $availability->end_time,
            $service->duration_minutes,
            $existingBookings,
            $date
        );

        return $slots;
    }

    /**
     * Generate available time slots based on working hours and existing bookings.
     */
    protected function generateTimeSlots(
        string $startTime,
        string $endTime,
        int $durationMinutes,
        Collection $existingBookings,
        string $date
    ): array {
        $slots = [];
        $slotInterval = 30; // 30-minute slot intervals

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

            // Check if slot conflicts with existing bookings
            $isAvailable = ! $this->hasConflict($current, $slotEnd, $existingBookings);

            if ($isAvailable) {
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
     * Check if a time slot conflicts with existing bookings.
     */
    protected function hasConflict(Carbon $start, Carbon $end, Collection $bookings): bool
    {
        foreach ($bookings as $booking) {
            $bookingStart = Carbon::parse($start->format('Y-m-d').' '.$booking->start_time);
            $bookingEnd = Carbon::parse($start->format('Y-m-d').' '.$booking->end_time);

            // Check for overlap
            if ($start->lt($bookingEnd) && $end->gt($bookingStart)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a specific slot is still available.
     */
    public function isSlotAvailable(Provider $provider, string $date, string $startTime, string $endTime): bool
    {
        $carbonDate = Carbon::parse($date);
        $dayOfWeek = $carbonDate->dayOfWeek;

        // Check provider works on this day
        $availability = $provider->availability()
            ->where('day_of_week', $dayOfWeek)
            ->where('is_available', true)
            ->first();

        if (! $availability) {
            return false;
        }

        // Check the slot is within working hours
        if ($startTime < $availability->start_time || $endTime > $availability->end_time) {
            return false;
        }

        // Check date is not blocked
        if ($provider->blockedDates()->where('date', $date)->exists()) {
            return false;
        }

        // Check for conflicting bookings
        $hasConflict = Booking::where('provider_id', $provider->id)
            ->where('booking_date', $date)
            ->whereIn('status', [BookingStatus::PENDING, BookingStatus::CONFIRMED])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    // New booking starts during existing booking
                    $q->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // New booking ends during existing booking
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // New booking encompasses existing booking
                    $q->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            })
            ->exists();

        return ! $hasConflict;
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
