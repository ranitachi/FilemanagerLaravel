<?php

namespace Fachran\FileManager\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Fachran\FileManager\Events\FileUploaded;
use Fachran\FileManager\Exceptions\AntivirusException;
use Fachran\FileManager\Exceptions\InvalidMimeTypeException;
use Fachran\FileManager\Exceptions\MimeMismatchException;
use Fachran\FileManager\Models\File;
use Fachran\FileManager\Repositories\Contracts\FileRepositoryInterface;
use Fachran\FileManager\Storage\Contracts\StorageAdapterInterface;

class UploadService
{
    public function __construct(
        protected StorageAdapterInterface $storage,
        protected FileRepositoryInterface $fileRepository,
        protected ThumbnailService $thumbnailService,
        protected AuditLogService $auditLog,
    ) {}

    /**
     * Main entry point — upload a file, save metadata, fire events.
     */
    public function handle(UploadedFile $uploadedFile, ?string $folderId = null): File
    {
        // 1. Validate MIME (deep)
        $this->validateMimeType($uploadedFile);

        // 2. Check magic bytes (anti PHP injection)
        $this->checkMagicBytes($uploadedFile);

        // 3. Antivirus scan (optional)
        if (config('filemanager.antivirus.enabled')) {
            $this->antivirusScan($uploadedFile);
        }

        // 4. Sanitize filename
        $safeName = $this->sanitizeFilename($uploadedFile->getClientOriginalName());

        // 5. Detect extension from real MIME (not client-provided)
        $extension = $this->getExtensionFromMime($uploadedFile);

        // 6. Generate UUID-based storage path (never uses original filename)
        $storagePath = $this->generateStoragePath($extension);

        // 7. Calculate checksum before storing
        $checksum = hash_file('sha256', $uploadedFile->getRealPath());

        // 8. Persist to storage
        $this->storage->put(
            $storagePath,
            file_get_contents($uploadedFile->getRealPath()),
            ['visibility' => 'private']
        );

        // 9. Generate thumbnail if image
        $thumbnailPath = null;
        if (str_starts_with($uploadedFile->getMimeType(), 'image/')) {
            $thumbnailPath = $this->thumbnailService->generate($uploadedFile, $storagePath);
        }

        // 10. Extract metadata (image dimensions, etc.)
        $metadata = $this->extractMetadata($uploadedFile);

        // 11. Save to database
        $file = $this->fileRepository->create([
            'folder_id'     => $folderId,
            'owner_id'      => auth()->id(),
            'name'          => $safeName,
            'original_name' => $uploadedFile->getClientOriginalName(),
            'storage_path'  => $storagePath,
            'disk'          => $this->storage->getDisk(),
            'mime_type'     => $uploadedFile->getMimeType(),
            'extension'     => $extension,
            'size'          => $uploadedFile->getSize(),
            'checksum'      => $checksum,
            'thumbnail_path'=> $thumbnailPath,
            'metadata'      => $metadata,
            'is_public'     => false,
            'created_by'    => auth()->id(),
        ]);

        // 12. Fire event
        event(new FileUploaded($file, auth()->user(), request()->ip()));

        return $file;
    }

    /**
     * Sanitize filename — strips all path traversal and dangerous chars.
     */
    public function sanitizeFilename(string $filename): string
    {
        // Strip any directory components
        $filename = basename($filename);

        // Remove null bytes
        $filename = str_replace("\0", '', $filename);

        // Remove non-safe characters (allow alphanumeric, dash, underscore, dot, space)
        $filename = preg_replace('/[^a-zA-Z0-9._\-\s]/', '', $filename);

        // Collapse multiple dots (prevent double extension like evil.php.jpg)
        $filename = preg_replace('/\.{2,}/', '.', $filename);

        // Trim trailing/leading dots and spaces
        $filename = trim($filename, '. ');

        // Fallback
        if (empty($filename)) {
            $filename = 'file_'.time();
        }

        return $filename;
    }

    /**
     * Validate MIME type using finfo (content-based, not extension-based).
     */
    public function validateMimeType(UploadedFile $file): void
    {
        $allowedMimes = config('filemanager.allowed_mimes', []);

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file->getRealPath());

        if (! in_array($realMime, $allowedMimes)) {
            throw new InvalidMimeTypeException(
                "File type [{$realMime}] is not allowed."
            );
        }

        // Cross-check MIME vs extension
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeMap = config('filemanager.mime_extension_map', []);

        if (isset($mimeMap[$realMime]) && ! in_array($extension, $mimeMap[$realMime])) {
            throw new MimeMismatchException(
                "Extension [{$extension}] does not match detected type [{$realMime}]."
            );
        }
    }

    /**
     * Check for PHP/script magic bytes in the file content.
     */
    protected function checkMagicBytes(UploadedFile $file): void
    {
        $handle = fopen($file->getRealPath(), 'rb');
        $bytes = fread($handle, 8);
        fclose($handle);

        $dangerous = ['<?php', '<?=', '<? ', '#!/', '#!'];
        foreach ($dangerous as $sig) {
            if (str_starts_with($bytes, $sig)) {
                throw new InvalidMimeTypeException('File contains executable code and cannot be uploaded.');
            }
        }
    }

    /**
     * Optional: scan with ClamAV via socket.
     */
    protected function antivirusScan(UploadedFile $file): void
    {
        $host = config('filemanager.antivirus.host');
        $port = config('filemanager.antivirus.port');
        $timeout = config('filemanager.antivirus.timeout', 30);

        try {
            $socket = fsockopen($host, $port, $errno, $errstr, $timeout);

            if (! $socket) {
                // AV not available — log warning but don't block upload
                \Log::warning("FileManager: ClamAV not available ({$errstr})");
                return;
            }

            fwrite($socket, "zSCAN {$file->getRealPath()}\0");
            $result = stream_get_contents($socket);
            fclose($socket);

            if (str_contains($result, 'FOUND')) {
                throw new AntivirusException("File failed antivirus scan: {$result}");
            }
        } catch (AntivirusException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::warning("FileManager: AV scan failed: {$e->getMessage()}");
        }
    }

    /**
     * Generate UUID-based storage path. The real filename is NEVER used in the path.
     * Format: filemanager/2025/01/a1b2c3d4-uuid.jpg
     */
    public function generateStoragePath(string $extension): string
    {
        $base = config('filemanager.storage_path', 'filemanager');
        $year = now()->format('Y');
        $month = now()->format('m');
        $uuid = Str::uuid()->toString();

        return "{$base}/{$year}/{$month}/{$uuid}.{$extension}";
    }

    protected function getExtensionFromMime(UploadedFile $file): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file->getRealPath());
        $mimeMap = config('filemanager.mime_extension_map', []);

        if (isset($mimeMap[$mime])) {
            return $mimeMap[$mime][0];
        }

        // fallback to client-provided extension (already sanitized upstream)
        return strtolower($file->getClientOriginalExtension()) ?: 'bin';
    }

    protected function extractMetadata(UploadedFile $file): array
    {
        $metadata = [];

        if (str_starts_with($file->getMimeType(), 'image/')) {
            $size = @getimagesize($file->getRealPath());
            if ($size) {
                $metadata['width']  = $size[0];
                $metadata['height'] = $size[1];
            }
        }

        return $metadata;
    }
}
