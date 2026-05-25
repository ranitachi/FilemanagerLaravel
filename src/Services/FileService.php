<?php

namespace Fachran\FileManager\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Fachran\FileManager\Events\FileDeleted;
use Fachran\FileManager\Events\FileDownloaded;
use Fachran\FileManager\Events\FileMoved;
use Fachran\FileManager\Exceptions\FileNotFoundException;
use Fachran\FileManager\Exceptions\PermissionDeniedException;
use Fachran\FileManager\Models\File;
use Fachran\FileManager\Repositories\Contracts\FileRepositoryInterface;
use Fachran\FileManager\Storage\Contracts\StorageAdapterInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileService
{
    public function __construct(
        protected FileRepositoryInterface $fileRepository,
        protected UploadService $uploadService,
        protected PermissionService $permissionService,
        protected AuditLogService $auditLog,
        protected StorageAdapterInterface $storage,
    ) {}

    /**
     * Browse files in a folder with pagination.
     */
    public function browse(?string $folderId = null, array $options = []): LengthAwarePaginator
    {
        return $this->fileRepository->paginate(
            folderId: $folderId,
            perPage:  $options['per_page'] ?? 20,
            sortBy:   $options['sort'] ?? 'name',
            sortDir:  $options['order'] ?? 'asc',
            search:   $options['search'] ?? null,
            mimeType: $options['mime_type'] ?? null,
        );
    }

    /**
     * Upload a file — delegates to UploadService.
     */
    public function upload(UploadedFile $file, ?string $folderId = null): File
    {
        return $this->uploadService->handle($file, $folderId);
    }

    /**
     * Download a file — stream response with access check.
     */
    public function download(string $fileId): StreamedResponse
    {
        $file = $this->findOrFail($fileId);
        $user = auth()->user();

        if (! $this->permissionService->can($user, $file, 'read')) {
            throw new PermissionDeniedException('You do not have permission to download this file.');
        }

        $file->incrementDownloadCount();

        event(new FileDownloaded($file, $user, request()->ip()));

        return response()->streamDownload(function () use ($file) {
            echo $this->storage->get($file->storage_path);
        }, $file->name, [
            'Content-Type'        => $file->mime_type,
            'Content-Disposition' => "attachment; filename=\"{$file->name}\"",
        ]);
    }

    /**
     * Stream file for preview (inline).
     */
    public function preview(string $fileId): StreamedResponse
    {
        $file = $this->findOrFail($fileId);
        $user = auth()->user();

        if (! $this->permissionService->can($user, $file, 'read')) {
            throw new PermissionDeniedException('You do not have permission to preview this file.');
        }

        $this->auditLog->log($file, 'view', $user);

        return response()->stream(function () use ($file) {
            echo $this->storage->get($file->storage_path);
        }, 200, [
            'Content-Type'        => $file->mime_type,
            'Content-Disposition' => "inline; filename=\"{$file->name}\"",
            'Cache-Control'       => 'private, max-age=3600',
        ]);
    }

    /**
     * Rename a file.
     */
    public function rename(string $fileId, string $newName): File
    {
        $file = $this->findOrFail($fileId);
        $user = auth()->user();

        if (! $this->permissionService->can($user, $file, 'write')) {
            throw new PermissionDeniedException('You do not have permission to rename this file.');
        }

        $oldName = $file->name;
        $safeName = $this->uploadService->sanitizeFilename($newName);

        $this->fileRepository->update($file, ['name' => $safeName]);

        $this->auditLog->log($file, 'rename', $user, ['name' => $oldName], ['name' => $safeName]);

        return $file->fresh();
    }

    /**
     * Move file to another folder.
     */
    public function move(string $fileId, ?string $targetFolderId): File
    {
        $file = $this->findOrFail($fileId);
        $user = auth()->user();

        if (! $this->permissionService->can($user, $file, 'write')) {
            throw new PermissionDeniedException('You do not have permission to move this file.');
        }

        $oldFolderId = $file->folder_id;
        $this->fileRepository->update($file, ['folder_id' => $targetFolderId]);

        event(new FileMoved($file, $oldFolderId, $targetFolderId, $user));
        $this->auditLog->log($file, 'move', $user, ['folder_id' => $oldFolderId], ['folder_id' => $targetFolderId]);

        return $file->fresh();
    }

    /**
     * Copy file to another folder.
     */
    public function copy(string $fileId, ?string $targetFolderId): File
    {
        $file = $this->findOrFail($fileId);
        $user = auth()->user();

        if (! $this->permissionService->can($user, $file, 'read')) {
            throw new PermissionDeniedException('You do not have permission to copy this file.');
        }

        // Copy physical file
        $newStoragePath = $this->uploadService->generateStoragePath($file->extension);
        $this->storage->copy($file->storage_path, $newStoragePath);

        // Create new DB record
        $newFile = $this->fileRepository->create([
            ...$file->only([
                'name', 'original_name', 'disk', 'mime_type',
                'extension', 'size', 'checksum', 'metadata', 'is_public',
            ]),
            'folder_id'    => $targetFolderId,
            'storage_path' => $newStoragePath,
            'owner_id'     => $user->id,
            'created_by'   => $user->id,
        ]);

        $this->auditLog->log($newFile, 'copy', $user, [], ['copied_from' => $fileId]);

        return $newFile;
    }

    /**
     * Soft delete (trash) or permanent delete.
     */
    public function delete(string $fileId, bool $permanent = false): bool
    {
        $file = $this->findOrFail($fileId);
        $user = auth()->user();

        if (! $this->permissionService->can($user, $file, 'delete')) {
            throw new PermissionDeniedException('You do not have permission to delete this file.');
        }

        if ($permanent) {
            // Only admins can permanently delete
            if (! $user->hasRole('super-admin')) {
                throw new PermissionDeniedException('Only administrators can permanently delete files.');
            }
            $this->storage->delete($file->storage_path);
            if ($file->thumbnail_path) {
                $this->storage->delete($file->thumbnail_path);
            }
            $this->fileRepository->forceDelete($file);
        } else {
            $this->fileRepository->softDelete($file);
        }

        event(new FileDeleted($file, $user, $permanent));
        $this->auditLog->log($file, 'delete', $user, [], ['permanent' => $permanent]);

        return true;
    }

    /**
     * Restore from trash.
     */
    public function restore(string $fileId): File
    {
        $file = $this->fileRepository->findTrashedOrFail($fileId);
        $user = auth()->user();

        if (! $this->permissionService->can($user, $file, 'write')) {
            throw new PermissionDeniedException('You do not have permission to restore this file.');
        }

        $this->fileRepository->restore($file);
        $this->auditLog->log($file, 'restore', $user);

        return $file->fresh();
    }

    public function find(string $fileId): ?File
    {
        return $this->fileRepository->find($fileId);
    }

    public function findOrFail(string $fileId): File
    {
        $file = $this->fileRepository->find($fileId);

        if (! $file) {
            throw new FileNotFoundException("File [{$fileId}] not found.");
        }

        return $file;
    }
}
