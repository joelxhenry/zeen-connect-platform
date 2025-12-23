<?php

namespace App\Console\Commands;

use App\Domains\Booking\Enums\BookingStatus;
use App\Domains\Booking\Models\Booking;
use App\Mail\BookingReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendBookingReminders extends Command
{
    protected $signature = 'bookings:send-reminders';

    protected $description = 'Send reminder emails for bookings scheduled for tomorrow';

    public function handle(): int
    {
        $tomorrow = now()->addDay()->toDateString();

        $bookings = Booking::with(['client', 'provider.user', 'service'])
            ->where('booking_date', $tomorrow)
            ->where('status', BookingStatus::CONFIRMED)
            ->whereNull('reminder_sent_at')
            ->get();

        $this->info("Found {$bookings->count()} bookings for tomorrow.");

        $sent = 0;

        foreach ($bookings as $booking) {
            try {
                // Send reminder to client
                Mail::to($booking->client->email)->send(new BookingReminder($booking, 'client'));

                // Send reminder to provider
                Mail::to($booking->provider->user->email)->send(new BookingReminder($booking, 'provider'));

                // Mark reminder as sent
                $booking->update(['reminder_sent_at' => now()]);

                $sent++;
                $this->line("Sent reminders for booking {$booking->uuid}");
            } catch (\Exception $e) {
                $this->error("Failed to send reminders for booking {$booking->uuid}: {$e->getMessage()}");
            }
        }

        $this->info("Successfully sent reminders for {$sent} bookings.");

        return Command::SUCCESS;
    }
}
