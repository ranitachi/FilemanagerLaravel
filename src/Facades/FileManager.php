<?php

namespace Fachran\FileManager\Facades;

use Illuminate\Support\Facades\Facade;
use Fachran\FileManager\Services\FileService;

/**
 * @method static \Fachran\FileManager\Models\File upload(\Illuminate\Http\UploadedFile $file, ?string $folderId = null)
 * @method static bool delete(string $fileId, bool $permanent = false)
 * @method static \Fachran\FileManager\Models\File rename(string $fileId, string $newName)
 * @method static \Fachran\FileManager\Models\File move(string $fileId, ?string $targetFolderId)
 * @method static \Fachran\FileManager\Models\File copy(string $fileId, ?string $targetFolderId)
 * @method static \Fachran\FileManager\Models\File findOrFail(string $fileId)
 * @method static \Illuminate\Contracts\Pagination\LengthAwarePaginator browse(?string $folderId = null, array $options = [])
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse download(string $fileId)
 * @method static \Symfony\Component\HttpFoundation\StreamedResponse preview(string $fileId)
 *
 * @see \Fachran\FileManager\Services\FileService
 */
class FileManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FileService::class;
    }
}
