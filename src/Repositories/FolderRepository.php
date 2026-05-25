<?php

namespace Fachran\FileManager\Repositories;

use Illuminate\Support\Collection;
use Fachran\FileManager\Models\Folder;
use Fachran\FileManager\Repositories\Contracts\FolderRepositoryInterface;

class FolderRepository implements FolderRepositoryInterface
{
    public function find(string $id): ?Folder
    {
        return Folder::find($id);
    }

    public function findOrFail(string $id): Folder
    {
        return Folder::findOrFail($id);
    }

    public function create(array $data): Folder
    {
        return Folder::create($data);
    }

    public function update(Folder $folder, array $data): Folder
    {
        $folder->update($data);
        return $folder;
    }

    public function softDelete(Folder $folder): bool
    {
        return $folder->delete();
    }

    public function tree(?string $parentId = null): Collection
    {
        $folders = Folder::query()
            ->when($parentId, fn ($q) => $q->where('parent_id', $parentId))
            ->when(! $parentId, fn ($q) => $q->whereNull('parent_id'))
            ->with(['children' => function ($q) {
                $q->withCount('files');
            }])
            ->withCount('files')
            ->orderBy('name')
            ->get();

        return $folders;
    }

    public function children(string $parentId): Collection
    {
        return Folder::where('parent_id', $parentId)
            ->withCount('files')
            ->orderBy('name')
            ->get();
    }
}
