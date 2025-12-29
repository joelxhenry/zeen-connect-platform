<?php

namespace App\Domains\Media\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UploadMediaRequest extends FormRequest
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
            'file' => ['required', 'file', 'mimes:' . $allowedMimes, 'max:' . $maxSize],
            'collection' => ['required', 'string', 'in:avatar,cover,gallery,review_images,logo'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.mimes' => 'Only JPEG, PNG, GIF, and WebP images are allowed.',
            'file.max' => 'The file size cannot exceed ' . (config('media.max_file_size', 10240) / 1024) . 'MB.',
            'collection.required' => 'Please specify the media collection.',
            'collection.in' => 'Invalid media collection specified.',
        ];
    }
}
