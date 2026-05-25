<?php

namespace Fachran\FileManager\Exceptions;

class InvalidMimeTypeException extends FileManagerException
{
    public function __construct(string $message = 'The uploaded file type is not allowed.')
    {
        parent::__construct($message, 422);
    }
}

