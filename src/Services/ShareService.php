<?php

namespace Fachran\FileManager\Services;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Fachran\FileManager\Exceptions\ShareExpiredException;
use Fachran\FileManager\Exceptions\ShareInvalidPasswordException;
use Fachran\FileManager\Exceptions\ShareLimitReachedException;
use Fachran\FileManager\Exceptions\ShareNotFoundException;
use Fachran\FileManager\Models\FileShare;
use Fachran\FileManager\Repositories\Contracts\FileRepositoryInterface;
use Fachran\FileManager\Storage\Contracts\StorageAdapterInterface;

class ShareService
{
    public function __construct(
        protected FileRepositoryInterface $fileRepository,
        protected PermissionService $permissionService,
        protected StorageAdapterInterface $storage,
    ) {}

    /**
     * Create a new share link for a file.
     */
    public function create(
        string $fileId,
        int $expiresInMinutes = 1440,
        ?int $maxDownloads = null,
        ?string $password = null,
    ): FileShare {
        $file = $this->fileRepository->findOrFail($fileId);
        $user = auth()->user();

        if (! $this->permissionService->can($user, $file, 'share')) {
            throw new \Fachran\FileManager\Exceptions\PermissionDeniedException(
                'You do not have permission to share this file.'
            );
        }

        return FileShare::create([
            'file_id'        => $file->id,
            'token'          => Str::random(64),
            'created_by'     => $user->getKey(),
            'expires_at'     => now()->addMinutes($expiresInMinutes),
            'max_downloads'  => $maxDownloads,
            'download_count' => 0,
            'password_hash'  => $password ? password_hash($password, PASSWORD_BCRYPT) : null,
        ]);
    }

    /**
     * Resolve and validate a share token.
     */
    public function resolve(string $token, ?string $password = null): FileShare
    {
        $share = FileShare::where('token', $token)->with('file')->first();

        if (! $share) {
            throw new ShareNotFoundException();
        }

        if ($share->isExpired()) {
            throw new ShareExpiredException();
        }

        if ($share->isLimitReached()) {
            throw new ShareLimitReachedException();
        }

        if ($share->hasPassword()) {
            if (! $password || ! $share->verifyPassword($password)) {
                throw new ShareInvalidPasswordException();
            }
        }

        return $share;
    }

    /**
     * Stream file download for a valid share token.
     */
    public function download(string $token, ?string $password = null): StreamedResponse
    {
        $share = $this->resolve($token, $password);
        $file  = $share->file;

        $share->incrementDownloadCount();

        return response()->streamDownload(function () use ($file) {
            echo $this->storage->get($file->storage_path);
        }, $file->name, [
            'Content-Type'        => $file->mime_type,
            'Content-Disposition' => "attachment; filename=\"{$file->name}\"",
        ]);
    }

    /**
     * Revoke / delete a share link.
     */
    public function revoke(string $token): bool
    {
        return (bool) FileShare::where('token', $token)
            ->where('created_by', auth()->id())
            ->delete();
    }
}
