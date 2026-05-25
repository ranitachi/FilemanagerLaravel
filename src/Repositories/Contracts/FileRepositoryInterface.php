<?php

namespace Fachran\FileManager\Repositories\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Fachran\FileManager\Models\File;

interface FileRepositoryInterface
{
    public function find(string $id): ?File;
    public function findOrFail(string $id): File;
    public function findTrashedOrFail(string $id): File;
    public function create(array $data): File;
    public function update(File $file, array $data): File;
    public function softDelete(File $file): bool;
    public function forceDelete(File $file): bool;
    public function restore(File $file): bool;
    public function paginate(
        ?string $folderId,
        int $perPage,
        string $sortBy,
        string $sortDir,
        ?string $search,
        ?string $mimeType
    ): LengthAwarePaginator;
}
