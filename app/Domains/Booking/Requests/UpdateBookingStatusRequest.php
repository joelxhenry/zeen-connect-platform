<?php

namespace App\Domains\Booking\Requests;

use App\Domains\Booking\Enums\BookingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isProvider();
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BookingStatus::class)],
            'reason' => ['nullable', 'required_if:status,cancelled', 'string', 'max:500'],
            'provider_notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required_if' => 'A reason is required when cancelling a booking.',
        ];
    }
}
