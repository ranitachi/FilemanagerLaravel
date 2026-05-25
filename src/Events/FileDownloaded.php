<?php

namespace Fachran\FileManager\Events;

use Fachran\FileManager\Models\File;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FileDownloaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly File $file,
        public readonly ?Authenticatable $downloadedBy,
        public readonly string $ip,
    ) {}
}

