<?php

namespace Fachran\FileManager\Storage;

use DateTimeInterface;
use Illuminate\Support\Facades\Storage;
use Fachran\FileManager\Storage\Contracts\StorageAdapterInterface;

class LocalAdapter implements StorageAdapterInterface
{
    protected string $disk;

    public function __construct()
    {
        $this->disk = config('filemanager.disk', 'local');
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
        // Local disk does not support real signed URLs; return a signed route instead
        return \URL::temporarySignedRoute(
            'filemanager.files.serve',
            $expiry,
            ['path' => base64_encode($path)]
        );
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
