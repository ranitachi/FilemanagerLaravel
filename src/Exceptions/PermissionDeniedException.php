<?php

namespace Fachran\FileManager\Exceptions;

class PermissionDeniedException extends FileManagerException
{
    public function __construct(string $message = 'You do not have permission to perform this action.')
    {
        parent::__construct($message, 403);
    }
}

