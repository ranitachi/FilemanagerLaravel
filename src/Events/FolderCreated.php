<?php

namespace Fachran\FileManager\Events;

use Fachran\FileManager\Models\Folder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FolderCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Folder $folder,
        public readonly Authenticatable $createdBy,
    ) {}
}

