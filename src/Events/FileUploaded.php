<?php

namespace Fachran\FileManager\Events;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Fachran\FileManager\Models\File;

class FileUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly File $file,
        public readonly Authenticatable $uploadedBy,
        public readonly string $ip,
    ) {}
}
