<?php

namespace App\Domains\Media\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class ImageProcessingService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /**
     * Process an uploaded image and generate conversions.
     *
     * @return array{original: string, conversions: array<string, string>}
     */
    public function processImage(
        UploadedFile $file,
        string $basePath,
        string $disk = 'public'
    ): array {
        $filename = $this->generateFilename($file);
        $storage = Storage::disk($disk);

        // Store original
        $originalPath = "{$basePath}/{$filename}";
        $storage->put($originalPath, $file->getContent());

        // Generate conversions
        $conversions = [];
        $image = $this->manager->read($file->getPathname());

        foreach (config('media.conversions', []) as $name => $settings) {
            $conversionPath = $this->generateConversionPath($basePath, $filename, $name);
            $this->createConversion($image, $conversionPath, $settings, $disk);
            $conversions[$name] = $conversionPath;
        }

        return [
            'original' => $originalPath,
            'conversions' => $conversions,
        ];
    }

    /**
     * Create a single conversion of an image.
     */
    protected function createConversion(
        \Intervention\Image\Interfaces\ImageInterface $image,
        string $path,
        array $settings,
        string $disk
    ): void {
        $width = $settings['width'];
        $height = $settings['height'];
        $fit = $settings['fit'] ?? 'contain';
        $quality = config('media.quality', 85);

        // Clone the image to avoid modifying the original
        $conversion = clone $image;

        switch ($fit) {
            case 'crop':
                $conversion->cover($width, $height);
                break;
            case 'fill':
                $conversion->pad($width, $height, 'ffffff');
                break;
            case 'contain':
            default:
                $conversion->scaleDown($width, $height);
                break;
        }

        // Encode and store
        $encoded = $conversion->toJpeg($quality);
        Storage::disk($disk)->put($path, $encoded->toString());
    }

    /**
     * Generate a unique filename for the uploaded file.
     */
    protected function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        return uniqid() . '_' . time() . '.' . $extension;
    }

    /**
     * Generate the path for a conversion.
     */
    protected function generateConversionPath(string $basePath, string $filename, string $conversion): string
    {
        $info = pathinfo($filename);
        $conversionFilename = "{$info['filename']}_{$conversion}.jpg";
        return "{$basePath}/conversions/{$conversionFilename}";
    }

    /**
     * Delete an image and all its conversions.
     */
    public function deleteImage(string $originalPath, array $conversions, string $disk = 'public'): void
    {
        $storage = Storage::disk($disk);

        if ($storage->exists($originalPath)) {
            $storage->delete($originalPath);
        }

        foreach ($conversions as $path) {
            if ($storage->exists($path)) {
                $storage->delete($path);
            }
        }
    }

    /**
     * Check if a file is a valid image.
     */
    public function isValidImage(UploadedFile $file): bool
    {
        $allowedMimeTypes = config('media.allowed_mime_types', [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ]);

        return in_array($file->getMimeType(), $allowedMimeTypes);
    }

    /**
     * Get image dimensions.
     *
     * @return array{width: int, height: int}
     */
    public function getImageDimensions(UploadedFile $file): array
    {
        $image = $this->manager->read($file->getPathname());

        return [
            'width' => $image->width(),
            'height' => $image->height(),
        ];
    }
}
