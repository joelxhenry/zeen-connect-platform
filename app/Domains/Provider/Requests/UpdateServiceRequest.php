<?php

namespace App\Domains\Provider\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateServiceRequest extends FormRequest
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
        ];
    }
}
