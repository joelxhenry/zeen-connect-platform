<?php

namespace App\Domains\Booking\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'provider_id' => ['required', 'exists:providers,id'],
            'service_id' => ['required', 'exists:services,id'],
            'date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];

        // Guest booking requires contact information
        if (! $this->user()) {
            $rules['guest_email'] = ['required', 'email', 'max:255'];
            $rules['guest_name'] = ['required', 'string', 'max:255'];
            $rules['guest_phone'] = ['required', 'string', 'max:50'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'date.after_or_equal' => 'The booking date must be today or in the future.',
            'guest_email.required' => 'Your email address is required to complete the booking.',
            'guest_name.required' => 'Your name is required to complete the booking.',
            'guest_phone.required' => 'Your phone number is required to complete the booking.',
        ];
    }

    /**
     * Check if this is a guest booking.
     */
    public function isGuestBooking(): bool
    {
        return ! $this->user();
    }
}
