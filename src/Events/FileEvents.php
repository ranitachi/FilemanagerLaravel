<?php

namespace Fachran\FileManager\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Fachran\FileManager\Models\File;

class FileDeleted
{
    use Dispatchable, SerializesModels;
    public function __construct(
        public readonly File $file,
        public readonly Authenticatable $deletedBy,
        public readonly bool $permanent,
    ) {}
}

class FileDownloaded
{
    use Dispatchable, SerializesModels;
    public function __construct(
        public readonly File $file,
        public readonly ?Authenticatable $downloadedBy,
        public readonly string $ip,
    ) {}
}

class FileMoved
{
    use Dispatchable, SerializesModels;
    public function __construct(
        public readonly File $file,
        public readonly ?string $fromFolderId,
        public readonly ?string $toFolderId,
        public readonly Authenticatable $movedBy,
    ) {}
}

class FolderCreated
{
    use Dispatchable, SerializesModels;
    public function __construct(
        public readonly \Fachran\FileManager\Models\Folder $folder,
        public readonly Authenticatable $createdBy,
    ) {}
}
