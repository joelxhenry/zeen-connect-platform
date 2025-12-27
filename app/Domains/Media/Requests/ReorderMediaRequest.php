<?php

namespace App\Domains\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ReorderMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['integer', 'exists:media,id'],
            'collection' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'ordered_ids.required' => 'Please provide the ordered list of media IDs.',
            'ordered_ids.array' => 'Invalid format for ordered IDs.',
            'ordered_ids.*.integer' => 'Each media ID must be a valid number.',
            'ordered_ids.*.exists' => 'One or more media items do not exist.',
            'collection.required' => 'Please specify the media collection.',
        ];
    }
}
