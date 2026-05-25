<?php

namespace Fachran\FileManager\Exceptions;

class MimeMismatchException extends FileManagerException
{
    public function __construct(string $message = 'File extension does not match its actual type.')
    {
        parent::__construct($message, 422);
    }
}

