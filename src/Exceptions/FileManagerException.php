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

