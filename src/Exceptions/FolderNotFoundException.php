<?php

namespace Fachran\FileManager\Exceptions;

class FolderNotFoundException extends FileManagerException
{
    public function __construct(string $message = 'Folder not found.')
    {
        parent::__construct($message, 404);
    }
}

