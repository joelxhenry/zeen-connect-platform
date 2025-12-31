<?php

namespace App\Domains\Provider\Requests;

use App\Domains\Event\Enums\EventLocationType;
use App\Domains\Event\Enums\EventType;
use App\Domains\Subscription\Services\SubscriptionService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isProvider();
    }

    public function rules(): array
    {
        return [
            // Basic information
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'description' => ['nullable', 'string', 'max:2000'],
            'event_type' => ['required', Rule::enum(EventType::class)],
            'location_type' => ['required', Rule::enum(EventLocationType::class)],
            'location' => ['nullable', 'required_if:location_type,in_person', 'string', 'max:255'],
            'virtual_meeting_url' => ['nullable', 'required_if:location_type,virtual', 'url', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:480'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],

            // Categories
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],

            // Team members
            'team_member_ids' => ['nullable', 'array'],
            'team_member_ids.*' => ['integer', 'exists:team_members,id'],

            // Booking settings
            'use_provider_defaults' => ['boolean'],
            'requires_approval' => ['nullable', 'boolean'],
            'deposit_type' => ['nullable', 'in:none,percentage'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cancellation_policy' => ['nullable', 'in:flexible,moderate,strict'],
            'advance_booking_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'min_booking_notice_hours' => ['nullable', 'integer', 'min:1', 'max:168'],
            'allow_waitlist' => ['boolean'],
            'max_spots_per_booking' => ['nullable', 'integer', 'min:1', 'max:100'],

            // One-time event occurrence
            'occurrence_datetime' => ['nullable', 'required_if:event_type,one_time', 'date', 'after:now'],

            // Recurrence settings (only validated for recurring events)
            'recurrence' => ['exclude_if:event_type,one_time', 'nullable', 'array'],
            'recurrence.frequency' => ['exclude_if:event_type,one_time', 'required_if:event_type,recurring', 'in:weekly'],
            'recurrence.interval' => ['exclude_if:event_type,one_time', 'nullable', 'integer', 'min:1', 'max:4'],
            'recurrence.days_of_week' => ['exclude_if:event_type,one_time', 'required_if:event_type,recurring', 'array', 'min:1'],
            'recurrence.days_of_week.*' => ['exclude_if:event_type,one_time', 'integer', 'min:0', 'max:6'],
            'recurrence.time_of_day' => ['exclude_if:event_type,one_time', 'required_if:event_type,recurring', 'date_format:H:i'],
            'recurrence.starts_at' => ['exclude_if:event_type,one_time', 'required_if:event_type,recurring', 'date', 'after_or_equal:today'],
            'recurrence.ends_at' => ['exclude_if:event_type,one_time', 'nullable', 'date', 'after:recurrence.starts_at'],
            'recurrence.max_occurrences' => ['exclude_if:event_type,one_time', 'nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    /**
     * Add tier-based validation after standard rules pass.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $provider = Auth::user()->provider;
            $subscriptionService = app(SubscriptionService::class);
            $restrictions = $subscriptionService->getTierRestrictions($provider);

            // Validate minimum event price
            $price = (float) $this->input('price', 0);
            if ($restrictions['minimum_service_price'] > 0 && $price < $restrictions['minimum_service_price']) {
                $validator->errors()->add(
                    'price',
                    "Your {$restrictions['tier_label']} tier requires a minimum price of {$restrictions['minimum_service_price_display']}."
                );
            }

            // Only validate deposit settings if not using provider defaults
            if (! $this->boolean('use_provider_defaults', true)) {
                $depositType = $this->input('deposit_type', 'none');
                $depositAmount = (float) $this->input('deposit_amount', 0);

                // Check if tier allows disabling deposit
                if ($depositType === 'none' && ! $restrictions['can_disable_deposit']) {
                    $validator->errors()->add(
                        'deposit_type',
                        "Your {$restrictions['tier_label']} tier requires a deposit to cover platform fees."
                    );
                }

                // Check minimum deposit percentage
                if ($depositType === 'percentage' && $depositAmount < $restrictions['minimum_deposit_percentage']) {
                    $validator->errors()->add(
                        'deposit_amount',
                        "Minimum deposit percentage for your tier is {$restrictions['minimum_deposit_percentage']}% to cover the platform fee."
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter an event name.',
            'name.min' => 'Event name must be at least 2 characters.',
            'name.max' => 'Event name cannot exceed 100 characters.',
            'description.max' => 'Description cannot exceed 2000 characters.',
            'event_type.required' => 'Please select an event type.',
            'location_type.required' => 'Please select a location type.',
            'location.required_if' => 'Please enter a location for in-person events.',
            'virtual_meeting_url.required_if' => 'Please enter a meeting URL for virtual events.',
            'virtual_meeting_url.url' => 'Please enter a valid URL.',
            'duration_minutes.required' => 'Please enter event duration.',
            'duration_minutes.min' => 'Duration must be at least 15 minutes.',
            'duration_minutes.max' => 'Duration cannot exceed 8 hours.',
            'capacity.min' => 'Capacity must be at least 1.',
            'capacity.max' => 'Capacity cannot exceed 10,000.',
            'price.required' => 'Please enter a price.',
            'price.min' => 'Price cannot be negative.',
            'price.max' => 'Price cannot exceed $999,999.99.',
            'occurrence_datetime.required_if' => 'Please select a date and time for the event.',
            'occurrence_datetime.after' => 'Event date must be in the future.',
            'recurrence.required_if' => 'Please configure recurrence settings for recurring events.',
            'recurrence.days_of_week.required_with' => 'Please select at least one day of the week.',
            'recurrence.days_of_week.min' => 'Please select at least one day of the week.',
            'recurrence.time_of_day.required_with' => 'Please select a time for the recurring event.',
            'recurrence.starts_at.required_with' => 'Please select a start date for recurrence.',
            'recurrence.starts_at.after_or_equal' => 'Recurrence start date must be today or later.',
            'recurrence.ends_at.after' => 'Recurrence end date must be after start date.',
        ];
    }
}
