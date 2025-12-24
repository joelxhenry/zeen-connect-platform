<?php

namespace App\Domains\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateSystemSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Tier Pricing
            'free_tier_platform_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'premium_tier_platform_fee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'premium_tier_monthly_price' => ['required', 'numeric', 'min:0'],
            'enterprise_tier_monthly_price' => ['required', 'numeric', 'min:0'],

            // Deposits
            'minimum_deposit_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'free_tier_deposit_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'minimum_deposit_amount' => ['required', 'numeric', 'min:0'],

            // Processing Fees
            'enterprise_processing_fee_rate' => ['required', 'numeric', 'min:0', 'max:10'],
            'enterprise_processing_fee_flat' => ['required', 'numeric', 'min:0'],

            // No-Show Policy
            'no_show_deposit_provider_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'no_show_deposit_platform_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Validate no-show percentages sum to 100
            $providerShare = (float) $this->input('no_show_deposit_provider_percentage', 0);
            $platformShare = (float) $this->input('no_show_deposit_platform_percentage', 0);

            if (abs(($providerShare + $platformShare) - 100) > 0.01) {
                $validator->errors()->add(
                    'no_show_deposit_provider_percentage',
                    'Provider and Platform shares must sum to 100%.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'free_tier_platform_fee_rate.max' => 'Platform fee cannot exceed 100%.',
            'premium_tier_platform_fee_rate.max' => 'Platform fee cannot exceed 100%.',
            'enterprise_processing_fee_rate.max' => 'Processing fee rate cannot exceed 10%.',
            'minimum_deposit_amount.min' => 'Minimum deposit amount must be at least 0.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'free_tier_platform_fee_rate' => 'Free Tier Platform Fee',
            'premium_tier_platform_fee_rate' => 'Premium Tier Platform Fee',
            'premium_tier_monthly_price' => 'Premium Tier Monthly Price',
            'enterprise_tier_monthly_price' => 'Enterprise Tier Monthly Price',
            'minimum_deposit_percentage' => 'Minimum Deposit Percentage',
            'free_tier_deposit_percentage' => 'Free Tier Deposit Percentage',
            'minimum_deposit_amount' => 'Minimum Deposit Amount',
            'enterprise_processing_fee_rate' => 'Processing Fee Rate',
            'enterprise_processing_fee_flat' => 'Processing Fee Flat',
            'no_show_deposit_provider_percentage' => 'Provider No-Show Share',
            'no_show_deposit_platform_percentage' => 'Platform No-Show Share',
        ];
    }
}
