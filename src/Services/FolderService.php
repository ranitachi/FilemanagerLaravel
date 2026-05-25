<?php

namespace Fachran\FileManager\Services;

use Illuminate\Support\Str;
use Fachran\FileManager\Events\FolderCreated;
use Fachran\FileManager\Exceptions\FolderNotFoundException;
use Fachran\FileManager\Exceptions\PermissionDeniedException;
use Fachran\FileManager\Models\Folder;
use Fachran\FileManager\Repositories\Contracts\FolderRepositoryInterface;

class FolderService
{
    public function __construct(
        protected FolderRepositoryInterface $folderRepository,
        protected PermissionService $permissionService,
        protected AuditLogService $auditLog,
    ) {}

    public function tree(?string $parentId = null): \Illuminate\Support\Collection
    {
        return $this->folderRepository->tree($parentId);
    }

    public function create(string $name, ?string $parentId = null, array $attributes = []): Folder
    {
        $user = auth()->user();

        $folder = $this->folderRepository->create([
            'name'        => $name,
            'slug'        => Str::slug($name),
            'parent_id'   => $parentId,
            'owner_id'    => $user->getKey(),
            'description' => $attributes['description'] ?? null,
            'is_public'   => $attributes['is_public'] ?? false,
            'created_by'  => $user->getKey(),
        ]);

        event(new FolderCreated($folder, $user));
        $this->auditLog->log($folder, 'folder_create', $user);

        return $folder;
    }

    public function rename(string $folderId, string $newName): Folder
    {
        $folder = $this->findOrFail($folderId);
        $user   = auth()->user();

        if (! $this->permissionService->can($user, $folder, 'write')) {
            throw new PermissionDeniedException('No permission to rename folder.');
        }

        $this->folderRepository->update($folder, [
            'name' => $newName,
            'slug' => Str::slug($newName),
        ]);

        return $folder->fresh();
    }

    public function delete(string $folderId): bool
    {
        $folder = $this->findOrFail($folderId);
        $user   = auth()->user();

        if (! $this->permissionService->can($user, $folder, 'delete')) {
            throw new PermissionDeniedException('No permission to delete folder.');
        }

        $this->auditLog->log($folder, 'folder_delete', $user);
        $this->folderRepository->softDelete($folder);

        return true;
    }

    public function findOrFail(string $folderId): Folder
    {
        $folder = $this->folderRepository->find($folderId);
        if (! $folder) {
            throw new FolderNotFoundException("Folder [{$folderId}] not found.");
        }
        return $folder;
    }
}
