<?php

namespace App\Domains\Provider\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateProviderProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isProvider();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $provider = Auth::user()->provider;

        return [
            'business_name' => ['required', 'string', 'min:2', 'max:100'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'address' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],

            // Social links
            'social_links' => ['nullable', 'array'],
            'social_links.instagram' => ['nullable', 'string', 'max:255'],
            'social_links.facebook' => ['nullable', 'string', 'max:255'],
            'social_links.twitter' => ['nullable', 'string', 'max:255'],
            'social_links.tiktok' => ['nullable', 'string', 'max:255'],
            'social_links.youtube' => ['nullable', 'string', 'max:255'],

            // Location
            'primary_location_id' => ['nullable', 'exists:locations,id'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['exists:locations,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'business_name.required' => 'Please enter your business name.',
            'business_name.min' => 'Business name must be at least 2 characters.',
            'business_name.max' => 'Business name cannot exceed 100 characters.',
            'bio.max' => 'Bio cannot exceed 1000 characters.',
            'website.url' => 'Please enter a valid website URL.',
            'primary_location_id.exists' => 'Please select a valid location.',
        ];
    }
}
