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
            'domain' => [
                'required',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-z0-9]+(-[a-z0-9]+)*$/',
                Rule::unique('providers', 'domain')->ignore($provider->id),
            ],

            // Social links
            'social_links' => ['nullable', 'array'],
            'social_links.instagram' => ['nullable', 'string', 'max:255'],
            'social_links.facebook' => ['nullable', 'string', 'max:255'],
            'social_links.twitter' => ['nullable', 'string', 'max:255'],
            'social_links.tiktok' => ['nullable', 'string', 'max:255'],
            'social_links.youtube' => ['nullable', 'string', 'max:255'],
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
            'domain.required' => 'Please enter a booking site URL.',
            'domain.min' => 'Booking site URL must be at least 3 characters.',
            'domain.max' => 'Booking site URL cannot exceed 50 characters.',
            'domain.regex' => 'Only lowercase letters, numbers, and hyphens are allowed. Cannot start or end with a hyphen.',
            'domain.unique' => 'This booking site URL is already taken. Please choose a different one.',
        ];
    }
}
