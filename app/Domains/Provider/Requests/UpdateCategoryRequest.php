<?php

namespace App\Domains\Provider\Requests;

use App\Domains\Service\Enums\CategoryType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->isProvider();
    }

    public function rules(): array
    {
        $provider = Auth::user()->provider;
        $category = $this->route('category');
        $type = $category?->type ?? CategoryType::SERVICE;

        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                Rule::unique('categories')
                    ->where('provider_id', $provider->id)
                    ->where('type', $type->value)
                    ->ignore($category?->id),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please enter a category name.',
            'name.min' => 'Category name must be at least 2 characters.',
            'name.max' => 'Category name cannot exceed 100 characters.',
            'name.unique' => 'You already have a category with this name.',
            'description.max' => 'Description cannot exceed 500 characters.',
        ];
    }
}
