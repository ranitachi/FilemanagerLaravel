<?php

namespace Fachran\FileManager\Storage\Contracts;

use DateTimeInterface;

interface StorageAdapterInterface
{
    public function put(string $path, mixed $contents, array $options = []): bool;

    public function get(string $path): string;

    public function delete(string $path): bool;

    public function exists(string $path): bool;

    public function url(string $path): string;

    public function temporaryUrl(string $path, DateTimeInterface $expiry, array $options = []): string;

    public function size(string $path): int;

    public function mimeType(string $path): string;

    public function move(string $from, string $to): bool;

    public function copy(string $from, string $to): bool;

    public function makeDirectory(string $path): bool;

    public function deleteDirectory(string $path): bool;

    public function files(string $directory = ''): array;

    public function directories(string $directory = ''): array;

    public function putFile(string $path, mixed $file, array $options = []): string|false;

    public function getDisk(): string;
}
