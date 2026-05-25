<?php

namespace Fachran\FileManager\Exceptions;

class AntivirusException extends FileManagerException
{
    public function __construct(string $message = 'File failed antivirus scan.')
    {
        parent::__construct($message, 422);
    }
}

