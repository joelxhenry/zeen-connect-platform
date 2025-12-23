<?php

namespace App\Domains\Review\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RespondToReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'response' => ['required', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'response.required' => 'Please enter your response.',
            'response.max' => 'Response cannot exceed 1000 characters.',
        ];
    }
}
