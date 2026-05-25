<?php

namespace Fachran\FileManager\Exceptions;

class ShareNotFoundException extends FileManagerException
{
    public function __construct(string $message = 'Share link not found.')
    {
        parent::__construct($message, 404);
    }
}

