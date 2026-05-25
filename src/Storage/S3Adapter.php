<?php

namespace Fachran\FileManager\Storage;

use Aws\S3\S3Client;
use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Fachran\FileManager\Storage\Contracts\StorageAdapterInterface;

class S3Adapter implements StorageAdapterInterface
{
    protected string $disk = 's3';

    public function __construct()
    {
        // Dynamically configure the s3 disk from filemanager config
        config([
            'filesystems.disks.filemanager_s3' => [
                'driver'   => 's3',
                'key'      => config('filemanager.s3.key'),
                'secret'   => config('filemanager.s3.secret'),
                'region'   => config('filemanager.s3.region'),
                'bucket'   => config('filemanager.s3.bucket'),
                'url'      => config('filemanager.s3.url'),
                'endpoint' => config('filemanager.s3.endpoint'),
                'use_path_style_endpoint' => config('filemanager.s3.endpoint') !== null,
            ],
        ]);

        $this->disk = 'filemanager_s3';
    }

    public function put(string $path, mixed $contents, array $options = []): bool
    {
        return Storage::disk($this->disk)->put($path, $contents, $options);
    }

    public function get(string $path): string
    {
        return Storage::disk($this->disk)->get($path);
    }

    public function delete(string $path): bool
    {
        return Storage::disk($this->disk)->delete($path);
    }

    public function exists(string $path): bool
    {
        return Storage::disk($this->disk)->exists($path);
    }

    public function url(string $path): string
    {
        return Storage::disk($this->disk)->url($path);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiry, array $options = []): string
    {
        return Storage::disk($this->disk)->temporaryUrl($path, $expiry, $options);
    }

    public function size(string $path): int
    {
        return Storage::disk($this->disk)->size($path);
    }

    public function mimeType(string $path): string
    {
        return Storage::disk($this->disk)->mimeType($path) ?? 'application/octet-stream';
    }

    public function move(string $from, string $to): bool
    {
        return Storage::disk($this->disk)->move($from, $to);
    }

    public function copy(string $from, string $to): bool
    {
        return Storage::disk($this->disk)->copy($from, $to);
    }

    public function makeDirectory(string $path): bool
    {
        return Storage::disk($this->disk)->makeDirectory($path);
    }

    public function deleteDirectory(string $path): bool
    {
        return Storage::disk($this->disk)->deleteDirectory($path);
    }

    public function files(string $directory = ''): array
    {
        return Storage::disk($this->disk)->files($directory);
    }

    public function directories(string $directory = ''): array
    {
        return Storage::disk($this->disk)->directories($directory);
    }

    public function putFile(string $path, mixed $file, array $options = []): string|false
    {
        return Storage::disk($this->disk)->putFile($path, $file, $options);
    }

    public function getDisk(): string
    {
        return $this->disk;
    }
}

// MinIO uses S3-compatible API — just set endpoint in config
class MinIOAdapter extends S3Adapter
{
    public function __construct()
    {
        parent::__construct(); // inherits S3 with endpoint configured
    }
}
