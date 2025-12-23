<?php

namespace App\Domains\Provider\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockedDatesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isProvider();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'blocked_dates' => ['present', 'array'],
            'blocked_dates.*.date' => ['required', 'date', 'after_or_equal:today'],
            'blocked_dates.*.reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'blocked_dates.*.date.after_or_equal' => 'Blocked dates must be today or in the future.',
        ];
    }
}
