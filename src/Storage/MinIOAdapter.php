<?php

namespace Fachran\FileManager\Storage;

/**
 * MinIO Adapter
 *
 * MinIO is S3-compatible — this adapter extends S3Adapter
 * and simply forces path-style endpoint usage (required for MinIO).
 *
 * .env example:
 *   FILEMANAGER_ADAPTER=Fachran\FileManager\Storage\MinIOAdapter
 *   FILEMANAGER_S3_ENDPOINT=http://minio:9000
 *   FILEMANAGER_S3_BUCKET=my-bucket
 *   FILEMANAGER_S3_KEY=minioadmin
 *   FILEMANAGER_S3_SECRET=minioadmin
 *   FILEMANAGER_S3_REGION=us-east-1
 */
class MinIOAdapter extends S3Adapter
{
    public function __construct()
    {
        // Force path-style endpoint (MinIO requires it)
        config([
            'filesystems.disks.filemanager_s3.use_path_style_endpoint' => true,
        ]);

        parent::__construct();
    }
}
