<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    | Supported drivers: "local", "s3", "minio"
    | You can create custom adapters by implementing StorageAdapterInterface.
    */
    'disk' => env('FILEMANAGER_DISK', 'local'),

    'storage_adapter' => env(
        'FILEMANAGER_ADAPTER',
        \Ranitachi\FileManager\Storage\LocalAdapter::class
    ),

    'storage_path' => env('FILEMANAGER_STORAGE_PATH', 'filemanager'),

    /*
    |--------------------------------------------------------------------------
    | S3 / MinIO Configuration
    |--------------------------------------------------------------------------
    */
    's3' => [
        'key'      => env('FILEMANAGER_S3_KEY', env('AWS_ACCESS_KEY_ID')),
        'secret'   => env('FILEMANAGER_S3_SECRET', env('AWS_SECRET_ACCESS_KEY')),
        'region'   => env('FILEMANAGER_S3_REGION', env('AWS_DEFAULT_REGION', 'ap-southeast-1')),
        'bucket'   => env('FILEMANAGER_S3_BUCKET', env('AWS_BUCKET')),
        'endpoint' => env('FILEMANAGER_S3_ENDPOINT'), // for MinIO: http://minio:9000
        'url'      => env('FILEMANAGER_S3_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload Constraints
    |--------------------------------------------------------------------------
    */
    'max_upload_size_kb' => env('FILEMANAGER_MAX_SIZE', 10240), // 10 MB

    'allowed_extensions' => [
        // Images
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'ico',
        // Documents
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
        // Media
        'mp4', 'mp3', 'wav', 'avi', 'mov',
        // Archives
        'zip', 'rar', '7z',
    ],

    'allowed_mimes' => [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'video/mp4', 'video/avi', 'video/quicktime',
        'audio/mpeg', 'audio/wav',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
    ],

    // Map MIME → allowed extensions (anti-spoofing)
    'mime_extension_map' => [
        'image/jpeg'        => ['jpg', 'jpeg'],
        'image/png'         => ['png'],
        'image/gif'         => ['gif'],
        'image/webp'        => ['webp'],
        'image/svg+xml'     => ['svg'],
        'application/pdf'   => ['pdf'],
        'text/plain'        => ['txt'],
        'text/csv'          => ['csv'],
        'video/mp4'         => ['mp4'],
        'audio/mpeg'        => ['mp3'],
        'application/zip'   => ['zip'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'uploads_per_minute'  => env('FILEMANAGER_UPLOAD_RATE', 20),
        'downloads_per_hour'  => env('FILEMANAGER_DOWNLOAD_RATE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnails
    |--------------------------------------------------------------------------
    */
    'thumbnails' => [
        'enabled' => env('FILEMANAGER_THUMBS', true),
        'width'   => 300,
        'height'  => 300,
        'quality' => 80,
        'driver'  => env('FILEMANAGER_IMAGE_DRIVER', 'gd'), // gd | imagick
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'signed_url_expire_minutes' => env('FILEMANAGER_SIGNED_URL_EXPIRE', 60),

    'antivirus' => [
        'enabled' => env('FILEMANAGER_AV_ENABLED', false),
        'driver'  => env('FILEMANAGER_AV_DRIVER', 'clamav'),
        'host'    => env('FILEMANAGER_AV_HOST', 'localhost'),
        'port'    => env('FILEMANAGER_AV_PORT', 3310),
        'timeout' => env('FILEMANAGER_AV_TIMEOUT', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes & Middleware
    |--------------------------------------------------------------------------
    */
    'route_prefix' => env('FILEMANAGER_ROUTE_PREFIX', 'filemanager'),

    'middleware' => [
        'web' => ['web', 'auth'],
        'api' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | WYSIWYG Editor Picker
    |--------------------------------------------------------------------------
    */
    'picker_types' => ['image', 'file', 'video', 'audio'],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes & Trash
    |--------------------------------------------------------------------------
    */
    'trash' => [
        'auto_purge_days' => env('FILEMANAGER_TRASH_PURGE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Events / Hooks
    |--------------------------------------------------------------------------
    | Add your custom listeners here. They will be automatically registered.
    */
    'listen' => [
        \Ranitachi\FileManager\Events\FileUploaded::class => [
            \Ranitachi\FileManager\Listeners\LogFileActivity::class,
        ],
        \Ranitachi\FileManager\Events\FileDeleted::class => [
            \Ranitachi\FileManager\Listeners\LogFileActivity::class,
        ],
        \Ranitachi\FileManager\Events\FileDownloaded::class => [
            \Ranitachi\FileManager\Listeners\LogFileActivity::class,
        ],
        \Ranitachi\FileManager\Events\FileMoved::class => [
            \Ranitachi\FileManager\Listeners\LogFileActivity::class,
        ],
        \Ranitachi\FileManager\Events\FolderCreated::class => [
            \Ranitachi\FileManager\Listeners\LogFileActivity::class,
        ],
    ],

];
