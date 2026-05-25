<?php

namespace Fachran\FileManager\Exceptions;

class ShareExpiredException extends FileManagerException
{
    public function __construct(string $message = 'This share link has expired.')
    {
        parent::__construct($message, 410);
    }
}

