<?php

namespace Fachran\FileManager\Exceptions;

class ShareLimitReachedException extends FileManagerException
{
    public function __construct(string $message = 'Download limit for this share link has been reached.')
    {
        parent::__construct($message, 410);
    }
}

