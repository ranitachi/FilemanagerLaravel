<?php

namespace Fachran\FileManager\Repositories\Contracts;

use Illuminate\Support\Collection;
use Fachran\FileManager\Models\Folder;

interface FolderRepositoryInterface
{
    public function find(string $id): ?Folder;
    public function findOrFail(string $id): Folder;
    public function create(array $data): Folder;
    public function update(Folder $folder, array $data): Folder;
    public function softDelete(Folder $folder): bool;
    public function tree(?string $parentId = null): Collection;
    public function children(string $parentId): Collection;
}
