<?php

namespace App\Domains\Provider\Requests;

use App\Domains\Subscription\Enums\SubscriptionFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBrandingSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        if (! Auth::check() || ! Auth::user()->isProvider()) {
            return false;
        }

        // Only Premium+ tiers can update branding
        return Auth::user()->provider->hasFeature(SubscriptionFeature::BRANDING);
    }

    public function rules(): array
    {
        $hexColorRule = ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'];

        return [
            'primary_color' => $hexColorRule,
            'text_color' => $hexColorRule,
            'success_color' => $hexColorRule,
            'warning_color' => $hexColorRule,
            'danger_color' => $hexColorRule,
            'info_color' => $hexColorRule,
            'secondary_color' => $hexColorRule,
        ];
    }

    public function messages(): array
    {
        return [
            '*.regex' => 'Each color must be a valid hex color (e.g., #3B82F6).',
        ];
    }
}
