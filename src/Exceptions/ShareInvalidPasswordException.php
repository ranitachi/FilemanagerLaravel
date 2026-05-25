<?php

namespace Fachran\FileManager\Exceptions;

class ShareInvalidPasswordException extends FileManagerException
{
    public function __construct(string $message = 'Invalid password for this share link.')
    {
        parent::__construct($message, 401);
    }
}

