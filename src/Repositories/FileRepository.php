<?php

namespace Fachran\FileManager\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Fachran\FileManager\Models\File;
use Fachran\FileManager\Repositories\Contracts\FileRepositoryInterface;

class FileRepository implements FileRepositoryInterface
{
    public function find(string $id): ?File
    {
        return File::find($id);
    }

    public function findOrFail(string $id): File
    {
        return File::findOrFail($id);
    }

    public function findTrashedOrFail(string $id): File
    {
        return File::onlyTrashed()->findOrFail($id);
    }

    public function create(array $data): File
    {
        return File::create($data);
    }

    public function update(File $file, array $data): File
    {
        $file->update($data);
        return $file;
    }

    public function softDelete(File $file): bool
    {
        return $file->delete();
    }

    public function forceDelete(File $file): bool
    {
        return $file->forceDelete();
    }

    public function restore(File $file): bool
    {
        return $file->restore();
    }

    public function paginate(
        ?string $folderId,
        int $perPage = 20,
        string $sortBy = 'name',
        string $sortDir = 'asc',
        ?string $search = null,
        ?string $mimeType = null,
    ): LengthAwarePaginator {
        $allowedSorts = ['name', 'size', 'created_at', 'updated_at', 'extension'];
        $sortBy  = in_array($sortBy, $allowedSorts) ? $sortBy : 'name';
        $sortDir = in_array(strtolower($sortDir), ['asc', 'desc']) ? $sortDir : 'asc';

        return File::query()
            ->when($folderId !== null, fn ($q) => $q->where('folder_id', $folderId))
            ->when($folderId === null, fn ($q) => $q->whereNull('folder_id'))
            ->when($search, fn ($q) => $q->where(function ($sub) use ($search) {
                $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%");
            }))
            ->when($mimeType, fn ($q) => $q->where('mime_type', 'like', "{$mimeType}%"))
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }
}
