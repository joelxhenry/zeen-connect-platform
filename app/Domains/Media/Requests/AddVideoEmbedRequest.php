<?php

namespace App\Domains\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AddVideoEmbedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        return [
            'url' => ['required_without:embed_code', 'nullable', 'url'],
            'embed_code' => ['required_without:url', 'nullable', 'string'],
            'title' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'url.required_without' => 'Please provide either a video URL or embed code.',
            'url.url' => 'Please provide a valid video URL.',
            'embed_code.required_without' => 'Please provide either a video URL or embed code.',
            'embed_code.string' => 'Invalid embed code format.',
            'title.max' => 'Video title cannot exceed 255 characters.',
        ];
    }
}
