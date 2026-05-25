# 🗂 Laravel Secure File Manager

[![Latest Version](https://img.shields.io/packagist/v/ranitachi/filemanager-laravel.svg)](https://packagist.org/packages/ranitachi/filemanager-laravel)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-10%2B%20%7C%2011%2B-red)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-green)](LICENSE)

A **secure, modular, and extensible** File Manager package for Laravel 10/11.  
Built as a drop-in replacement for `unisharp/laravel-filemanager` with enterprise-grade security, granular RBAC, multi-storage support, and native WYSIWYG editor integration.

---

## ✨ Features

| Feature | Details |
|---------|---------|
| 🔐 **Security** | Path traversal prevention, magic-byte MIME validation, signed URLs, ClamAV hook |
| 🗄 **Multi-Storage** | Local, S3, MinIO — swap via config, no code change |
| 👥 **RBAC** | Per-file / per-folder permissions for users and roles, with inheritance |
| 📋 **Audit Log** | Every action (upload, download, delete, move) is logged with IP and user-agent |
| 🖼 **Thumbnails** | Auto-generated via Intervention Image 3 (GD or Imagick) |
| ✂️ **Full CRUD** | Upload, rename, move, copy, soft-delete, restore, permanent delete |
| 🔗 **Share Links** | Temporary signed URLs with expiry, download limit, and optional password |
| 🧩 **Editor Integration** | CKEditor 5, TinyMCE, Summernote — single-file JS plugins included |
| 🎣 **Event Hooks** | `FileUploaded`, `FileDeleted`, `FileDownloaded`, `FileMoved`, `FolderCreated` |
| 📦 **Artisan Commands** | `filemanager:install`, `filemanager:purge-trash` |
| ⚡ **Rate Limiting** | Configurable upload rate limit per user/IP |

---

## Requirements

- **PHP** 8.1+
- **Laravel** 10.x or 11.x
- **Database** MySQL 8.0+ / PostgreSQL 13+ / SQLite (testing)
- **PHP Extensions** `gd` or `imagick` (for thumbnails), `finfo` (MIME detection)

---

## Installation

### 1. Install via Composer

```bash
composer require ranitachi/filemanager-laravel
```

### 2. Run the installer

```bash
php artisan filemanager:install
```

This publishes config, migrations, and assets — then runs migrations automatically.

### 3. Manual install (alternative)

```bash
php artisan vendor:publish --tag=filemanager-config
php artisan vendor:publish --tag=filemanager-migrations
php artisan vendor:publish --tag=filemanager-assets
php artisan migrate
php artisan storage:link
```

---

## Configuration

After publishing, edit `config/filemanager.php`:

```php
// Storage driver
'disk'            => env('FILEMANAGER_DISK', 'local'),
'storage_adapter' => \Ranitachi\FileManager\Storage\LocalAdapter::class,

// Upload constraints
'max_upload_size_kb' => 10240,   // 10 MB
'allowed_extensions' => ['jpg', 'png', 'pdf', 'docx', ...],

// Rate limiting
'rate_limit' => [
    'uploads_per_minute' => 20,
],

// Thumbnails
'thumbnails' => [
    'enabled' => true,
    'width'   => 300,
    'height'  => 300,
    'driver'  => 'gd',   // or 'imagick'
],
```

### `.env` Variables

```env
FILEMANAGER_DISK=local
FILEMANAGER_MAX_SIZE=10240
FILEMANAGER_SIGNED_URL_EXPIRE=60

# S3 / MinIO
FILEMANAGER_ADAPTER=Ranitachi\FileManager\Storage\S3Adapter
FILEMANAGER_S3_KEY=your-key
FILEMANAGER_S3_SECRET=your-secret
FILEMANAGER_S3_REGION=ap-southeast-1
FILEMANAGER_S3_BUCKET=your-bucket

# MinIO only
FILEMANAGER_S3_ENDPOINT=http://minio:9000

# ClamAV (optional)
FILEMANAGER_AV_ENABLED=false
FILEMANAGER_AV_HOST=localhost
FILEMANAGER_AV_PORT=3310
```

---

## API Reference

All API routes are prefixed with `/api/v1/filemanager` and require a Bearer token.

### Files

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/files` | Browse files (with `folder_id`, `search`, `sort` params) |
| `POST` | `/upload` | Upload a file (multipart) |
| `GET` | `/files/{id}` | Get file metadata |
| `GET` | `/files/{id}/download` | Download file |
| `GET` | `/files/{id}/preview` | Preview file (inline stream) |
| `PATCH` | `/files/{id}/rename` | Rename file |
| `POST` | `/files/{id}/move` | Move to another folder |
| `POST` | `/files/{id}/copy` | Copy to another folder |
| `POST` | `/files/{id}/restore` | Restore from trash |
| `DELETE` | `/files/{id}` | Soft delete (pass `permanent: true` for hard delete) |

### Folders

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/folders` | Folder tree |
| `POST` | `/folders` | Create folder |
| `PATCH` | `/folders/{id}` | Rename folder |
| `DELETE` | `/folders/{id}` | Delete folder |

### Permissions

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/{type}/{id}/permissions` | List permissions on file/folder |
| `POST` | `/{type}/{id}/permissions` | Grant permission to user/role |
| `DELETE` | `/permissions/{id}` | Revoke a specific permission |

### Share Links

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/files/{id}/share` | Create a share link |
| `DELETE` | `/share/{token}/revoke` | Revoke a share link |
| `GET` | `/share/{token}` | Public share page |
| `GET` | `/share/{token}/download` | Public download |

---

## Usage Examples

### Via Facade

```php
use Ranitachi\FileManager\Facades\FileManager;

// Upload
$file = FileManager::upload($request->file('document'), $folderId);

// Browse with pagination
$files = FileManager::browse($folderId, [
    'per_page' => 20,
    'sort'     => 'created_at',
    'order'    => 'desc',
    'search'   => 'report',
]);

// Download (returns StreamedResponse)
return FileManager::download($fileId);

// Move
FileManager::move($fileId, $newFolderId);

// Delete (soft)
FileManager::delete($fileId);

// Delete (permanent — admin only)
FileManager::delete($fileId, permanent: true);
```

### Granting Permissions

```php
use Ranitachi\FileManager\Services\PermissionService;

$service = app(PermissionService::class);

// Grant read+write to a specific user on a folder
$service->grant(
    resource:      $folder,
    grantableId:   $userId,
    grantableType: App\Models\User::class,
    permissions:   ['read', 'write'],
    expiresAt:     now()->addDays(30),
);

// Check permission
$canDelete = $service->can($user, $file, 'delete'); // bool
```

### Creating a Share Link

```php
use Ranitachi\FileManager\Services\ShareService;

$share = app(ShareService::class)->create(
    fileId:           $file->id,
    expiresInMinutes: 1440,    // 24 hours
    maxDownloads:     10,
    password:         'secret',
);

echo $share->getShareUrl(); // https://yourapp.com/filemanager/share/AbCdEf...
```

### Listening to Events

```php
// In EventServiceProvider or config/filemanager.php 'listen' key:

use Ranitachi\FileManager\Events\FileUploaded;

class NotifyOnUpload
{
    public function handle(FileUploaded $event): void
    {
        \Log::info("File uploaded: {$event->file->name} by user {$event->uploadedBy->id}");
    }
}
```

---

## WYSIWYG Editor Integration

### CKEditor 5

```html
<script src="/vendor/filemanager/js/ckeditor-plugin.js" type="module"></script>
<script type="module">
import { FileManagerPlugin } from '/vendor/filemanager/js/ckeditor-plugin.js';

ClassicEditor.create(document.querySelector('#editor'), {
    extraPlugins: [ FileManagerPlugin ],
    fileManager:  { pickerUrl: '/filemanager/picker' },
});
</script>
```

### TinyMCE

```html
<script src="/vendor/filemanager/js/editors.js"></script>
<script>
FileManagerTinyMCE.init('#editor', {
    pickerUrl: '/filemanager/picker',
});
</script>
```

### Summernote

```html
<script src="/vendor/filemanager/js/editors.js"></script>
<script>
$('#editor').summernote(
    FileManagerSummernote.config({ pickerUrl: '/filemanager/picker' })
);
</script>
```

The picker opens as a popup window. When a file is selected, a callback fires in the parent window and automatically inserts the file URL into the editor.

---

## Artisan Commands

```bash
# One-step installation wizard
php artisan filemanager:install

# Purge files trashed more than 30 days ago (configurable)
php artisan filemanager:purge-trash

# Dry run (show what would be deleted)
php artisan filemanager:purge-trash --dry-run

# Custom retention period
php artisan filemanager:purge-trash --days=7

# Schedule it in Console/Kernel.php or routes/console.php
$schedule->command('filemanager:purge-trash --force')->weekly();
```

---

## Security

This package implements multiple layers of defence:

**Upload Security**
- MIME type validated with PHP `finfo` (content-based, not extension-based)
- Extension cross-checked against MIME whitelist to prevent spoofing
- Magic bytes checked (blocks `<?php`, `#!/`, etc.)
- Storage path is UUID-based — original filename is **never** used in the path
- Filename is sanitised (strips `../`, null bytes, dangerous characters)
- Optional ClamAV antivirus scan hook

**Access Control**
- All endpoints require authentication
- Permission resolution: Super Admin → Owner → Explicit User → Role → Inherited → Deny
- Permissions support expiry dates
- Rate limiting on upload endpoints (configurable)

**Share Links**
- 64-character random token
- Configurable expiry + download limit
- Optional password protection (bcrypt)
- Signed URL for local storage previews

---

## Testing

```bash
composer test

# With coverage
composer test-coverage
```

The test suite uses **PestPHP** and an in-memory SQLite database. Storage is faked via `Storage::fake()`.

---

## Publishing to Packagist

1. Update `composer.json` sesuai vendor/package final Anda di Packagist
2. Push to a public GitHub repository
3. Go to [packagist.org/packages/submit](https://packagist.org/packages/submit) and enter your repository URL
4. Set up the GitHub webhook for auto-updates on new releases

---

## Roadmap

- [ ] Chunked upload for large files (> 100 MB)
- [ ] File versioning with restore to previous version
- [ ] CDN integration (CloudFront, Cloudflare, BunnyCDN)
- [ ] Image optimization (auto-resize, WebP conversion)
- [ ] AI auto-tagging via OpenAI Vision / Gemini
- [ ] OCR text extraction (Tesseract / AWS Textract)
- [ ] Multi-tenant storage isolation
- [ ] Goravel (Go) port for high-throughput microservice

---

## License

MIT © [Fachran](https://github.com/ranitachi)
