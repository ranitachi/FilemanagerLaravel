<?php

namespace Fachran\FileManager\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class FileManagerException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], $this->getCode() ?: 500);
    }
}

class FileNotFoundException extends FileManagerException
{
    public function __construct(string $message = 'File not found.')
    {
        parent::__construct($message, 404);
    }
}

class FolderNotFoundException extends FileManagerException
{
    public function __construct(string $message = 'Folder not found.')
    {
        parent::__construct($message, 404);
    }
}

class PermissionDeniedException extends FileManagerException
{
    public function __construct(string $message = 'You do not have permission to perform this action.')
    {
        parent::__construct($message, 403);
    }
}

class InvalidMimeTypeException extends FileManagerException
{
    public function __construct(string $message = 'The uploaded file type is not allowed.')
    {
        parent::__construct($message, 422);
    }
}

class MimeMismatchException extends FileManagerException
{
    public function __construct(string $message = 'File extension does not match its actual type.')
    {
        parent::__construct($message, 422);
    }
}

class AntivirusException extends FileManagerException
{
    public function __construct(string $message = 'File failed antivirus scan.')
    {
        parent::__construct($message, 422);
    }
}

class ShareNotFoundException extends FileManagerException
{
    public function __construct(string $message = 'Share link not found.')
    {
        parent::__construct($message, 404);
    }
}

class ShareExpiredException extends FileManagerException
{
    public function __construct(string $message = 'This share link has expired.')
    {
        parent::__construct($message, 410);
    }
}

class ShareLimitReachedException extends FileManagerException
{
    public function __construct(string $message = 'Download limit for this share link has been reached.')
    {
        parent::__construct($message, 410);
    }
}

class ShareInvalidPasswordException extends FileManagerException
{
    public function __construct(string $message = 'Invalid password for this share link.')
    {
        parent::__construct($message, 401);
    }
}
