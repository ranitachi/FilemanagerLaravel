<?php

namespace Fachran\FileManager\Exceptions;

class FileNotFoundException extends FileManagerException
{
    public function __construct(string $message = 'File not found.')
    {
        parent::__construct($message, 404);
    }
}

