<?php

namespace App\Domains\Provider\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isProvider();
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            // Booking settings
            'use_provider_defaults' => ['boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'deposit_type' => ['nullable', 'in:none,fixed,percentage'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'cancellation_policy' => ['nullable', 'in:flexible,moderate,strict'],
            'advance_booking_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'min_booking_notice_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'Please select a valid category.',
            'name.required' => 'Please enter a service name.',
            'name.min' => 'Service name must be at least 2 characters.',
            'name.max' => 'Service name cannot exceed 100 characters.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'duration_minutes.required' => 'Please enter service duration.',
            'duration_minutes.min' => 'Duration must be at least 15 minutes.',
            'duration_minutes.max' => 'Duration cannot exceed 8 hours.',
            'price.required' => 'Please enter a price.',
            'price.min' => 'Price cannot be negative.',
            'price.max' => 'Price cannot exceed $999,999.99.',
            'deposit_amount.min' => 'Deposit amount cannot be negative.',
            'advance_booking_days.min' => 'Advance booking days must be at least 1.',
            'advance_booking_days.max' => 'Advance booking days cannot exceed 365.',
            'min_booking_notice_hours.min' => 'Minimum booking notice must be at least 1 hour.',
            'min_booking_notice_hours.max' => 'Minimum booking notice cannot exceed 168 hours (1 week).',
        ];
    }
}
