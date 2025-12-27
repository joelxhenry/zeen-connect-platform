<?php

namespace App\Domains\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UploadMultipleMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    public function rules(): array
    {
        $maxSize = config('media.max_file_size', 10240);
        $allowedMimes = implode(',', array_map(function ($mime) {
            return explode('/', $mime)[1];
        }, config('media.allowed_mime_types', ['image/jpeg', 'image/png', 'image/gif', 'image/webp'])));

        return [
            'files' => ['required', 'array', 'max:5'],
            'files.*' => ['file', 'mimes:' . $allowedMimes, 'max:' . $maxSize],
            'collection' => ['required', 'string', 'in:gallery,review_images'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Please select files to upload.',
            'files.array' => 'Invalid files format.',
            'files.max' => 'You can upload a maximum of 5 files at once.',
            'files.*.file' => 'One of the uploaded files is invalid.',
            'files.*.mimes' => 'Only JPEG, PNG, GIF, and WebP images are allowed.',
            'files.*.max' => 'Each file size cannot exceed ' . (config('media.max_file_size', 10240) / 1024) . 'MB.',
            'collection.required' => 'Please specify the media collection.',
            'collection.in' => 'Invalid media collection specified.',
        ];
    }
}
