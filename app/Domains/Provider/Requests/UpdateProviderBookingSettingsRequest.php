<?php

namespace App\Domains\Provider\Requests;

use App\Domains\Subscription\Services\SubscriptionService;
use Illuminate\Contracts\Validation\Validator;
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
            'deposit_type' => ['required', 'in:none,percentage'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'cancellation_policy' => ['required', 'in:flexible,moderate,strict'],
            'advance_booking_days' => ['required', 'integer', 'min:1', 'max:365'],
            'min_booking_notice_hours' => ['required', 'integer', 'min:1', 'max:168'],
            'fee_payer' => ['sometimes', 'in:provider,client'],
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
        });
    }

    public function messages(): array
    {
        return [
            'deposit_amount.min' => 'Deposit percentage cannot be negative.',
            'deposit_amount.max' => 'Deposit percentage cannot exceed 100%.',
            'advance_booking_days.min' => 'Advance booking days must be at least 1.',
            'advance_booking_days.max' => 'Advance booking days cannot exceed 365.',
            'min_booking_notice_hours.min' => 'Minimum booking notice must be at least 1 hour.',
            'min_booking_notice_hours.max' => 'Minimum booking notice cannot exceed 168 hours (1 week).',
        ];
    }
}
