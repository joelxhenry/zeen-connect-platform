<?php

namespace App\Domains\Provider\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateProviderBookingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isProvider();
    }

    public function rules(): array
    {
        return [
            'requires_approval' => ['required', 'boolean'],
            'deposit_type' => ['required', 'in:none,fixed,percentage'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'cancellation_policy' => ['required', 'in:flexible,moderate,strict'],
            'advance_booking_days' => ['required', 'integer', 'min:1', 'max:365'],
            'min_booking_notice_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'fee_payer' => ['sometimes', 'in:provider,client'],
        ];
    }

    public function messages(): array
    {
        return [
            'deposit_amount.min' => 'Deposit amount cannot be negative.',
            'advance_booking_days.min' => 'Advance booking days must be at least 1.',
            'advance_booking_days.max' => 'Advance booking days cannot exceed 365.',
            'min_booking_notice_hours.min' => 'Minimum booking notice must be at least 1 hour.',
            'min_booking_notice_hours.max' => 'Minimum booking notice cannot exceed 168 hours (1 week).',
        ];
    }
}
