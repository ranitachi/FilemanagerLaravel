<?php

declare(strict_types=1);

/**
 * Namespace compatibility layer:
 * - Old namespace remains source of truth: Fachran\FileManager\*
 * - New namespace aliases for forward branding: Ranitachi\FileManager\*
 */

$classAliases = [
    // Root
    'FileManagerServiceProvider',

    // Console
    'Console\\Commands\\InstallCommand',
    'Console\\Commands\\PurgeTrashCommand',

    // Events & listeners
    'Events\\FileDeleted',
    'Events\\FileDownloaded',
    'Events\\FileMoved',
    'Events\\FolderCreated',
    'Events\\FileUploaded',
    'Listeners\\LogFileActivity',

    // Exceptions / Facade
    'Exceptions\\FileManagerException',
    'Exceptions\\FileNotFoundException',
    'Exceptions\\FolderNotFoundException',
    'Exceptions\\PermissionDeniedException',
    'Exceptions\\InvalidMimeTypeException',
    'Exceptions\\MimeMismatchException',
    'Exceptions\\AntivirusException',
    'Exceptions\\ShareNotFoundException',
    'Exceptions\\ShareExpiredException',
    'Exceptions\\ShareLimitReachedException',
    'Exceptions\\ShareInvalidPasswordException',
    'Facades\\FileManager',

    // HTTP
    'Http\\Controllers\\EditorCallbackController',
    'Http\\Controllers\\FileController',
    'Http\\Controllers\\FolderController',
    'Http\\Controllers\\PermissionController',
    'Http\\Controllers\\ShareController',
    'Http\\Middleware\\RateLimitUpload',
    'Http\\Requests\\UploadFileRequest',
    'Http\\Resources\\FileResource',
    'Http\\Resources\\FolderResource',

    // Models
    'Models\\File',
    'Models\\FileLog',
    'Models\\FilePermission',
    'Models\\FileShare',
    'Models\\Folder',

    // Repositories
    'Repositories\\FileRepository',
    'Repositories\\FolderRepository',
    'Repositories\\Contracts\\FileRepositoryInterface',
    'Repositories\\Contracts\\FolderRepositoryInterface',

    // Services
    'Services\\AuditLogService',
    'Services\\FileService',
    'Services\\FolderService',
    'Services\\PermissionService',
    'Services\\ShareService',
    'Services\\ThumbnailService',
    'Services\\UploadService',

    // Storage
    'Storage\\LocalAdapter',
    'Storage\\MinIOAdapter',
    'Storage\\S3Adapter',
    'Storage\\Contracts\\StorageAdapterInterface',
];

foreach ($classAliases as $suffix) {
    $legacy = 'Fachran\\FileManager\\' . $suffix;
    $modern = 'Ranitachi\\FileManager\\' . $suffix;

    if (!class_exists($modern, false) && !interface_exists($modern, false)) {
        if (class_exists($legacy) || interface_exists($legacy)) {
            class_alias($legacy, $modern);
        }
    }
}
