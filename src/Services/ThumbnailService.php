<?php

namespace Fachran\FileManager\Services;

use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Fachran\FileManager\Storage\Contracts\StorageAdapterInterface;

class ThumbnailService
{
    protected ImageManager $manager;

    public function __construct(protected StorageAdapterInterface $storage)
    {
        $driver = config('filemanager.thumbnails.driver', 'gd') === 'imagick'
            ? 'imagick'
            : 'gd';

        $this->manager = new ImageManager(['driver' => $driver]);
    }

    /**
     * Generate a thumbnail for an image file.
     * Returns the thumbnail storage path, or null if generation failed.
     */
    public function generate(UploadedFile $file, string $originalStoragePath): ?string
    {
        if (! config('filemanager.thumbnails.enabled', true)) {
            return null;
        }

        if (! str_starts_with($file->getMimeType(), 'image/')) {
            return null;
        }

        // SVG thumbnails not supported
        if ($file->getMimeType() === 'image/svg+xml') {
            return null;
        }

        try {
            $width   = config('filemanager.thumbnails.width', 300);
            $height  = config('filemanager.thumbnails.height', 300);
            $quality = config('filemanager.thumbnails.quality', 80);

            $image = $this->manager->make($file->getRealPath());
            $image->fit($width, $height);

            // Derive thumbnail path from original (insert _thumb before extension)
            $thumbPath = $this->getThumbnailPath($originalStoragePath);

            $this->storage->put($thumbPath, (string) $image->encode('jpg', $quality));

            return $thumbPath;
        } catch (\Throwable $e) {
            \Log::warning("FileManager: Thumbnail generation failed: {$e->getMessage()}");
            return null;
        }
    }

    protected function getThumbnailPath(string $originalPath): string
    {
        $info = pathinfo($originalPath);
        return $info['dirname']
            . '/'
            . $info['filename']
            . '_thumb.jpg';
    }
}
