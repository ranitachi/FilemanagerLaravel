<?php

namespace Fachran\FileManager\Events;

use Fachran\FileManager\Models\File;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

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

