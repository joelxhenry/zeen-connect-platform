<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | The default disk for storing media files. This can be overridden
    | per upload request.
    |
    */
    'disk' => env('MEDIA_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Max File Size
    |--------------------------------------------------------------------------
    |
    | Maximum file size in kilobytes for uploaded files.
    |
    */
    'max_file_size' => env('MEDIA_MAX_FILE_SIZE', 10240), // 10MB

    /*
    |--------------------------------------------------------------------------
    | Allowed Mime Types
    |--------------------------------------------------------------------------
    |
    | List of allowed mime types for image uploads.
    |
    */
    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Conversions
    |--------------------------------------------------------------------------
    |
    | Define the image conversions (thumbnail sizes) to generate.
    |
    */
    'conversions' => [
        'thumbnail' => [
            'width' => 150,
            'height' => 150,
            'fit' => 'crop', // crop, contain, fill
        ],
        'medium' => [
            'width' => 600,
            'height' => 600,
            'fit' => 'contain',
        ],
        'large' => [
            'width' => 1200,
            'height' => 1200,
            'fit' => 'contain',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Quality
    |--------------------------------------------------------------------------
    |
    | Quality setting for processed images (1-100).
    |
    */
    'quality' => env('MEDIA_IMAGE_QUALITY', 85),

    /*
    |--------------------------------------------------------------------------
    | Collection Limits
    |--------------------------------------------------------------------------
    |
    | Maximum number of media items allowed per collection.
    |
    */
    'collection_limits' => [
        'avatar' => 1,
        'cover' => 1,
        'gallery' => 5,
        'review_images' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Platforms
    |--------------------------------------------------------------------------
    |
    | Supported video platforms and their URL patterns.
    |
    */
    'video_platforms' => [
        'youtube' => [
            'patterns' => [
                '/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/',
                '/youtu\.be\/([a-zA-Z0-9_-]+)/',
                '/youtube\.com\/embed\/([a-zA-Z0-9_-]+)/',
            ],
        ],
        'vimeo' => [
            'patterns' => [
                '/vimeo\.com\/(\d+)/',
                '/player\.vimeo\.com\/video\/(\d+)/',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Base paths for storing different types of media.
    |
    */
    'paths' => [
        'providers' => 'media/providers',
        'services' => 'media/services',
        'reviews' => 'media/reviews',
    ],
];
