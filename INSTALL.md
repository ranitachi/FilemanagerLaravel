# Installation Guide

## Requirements

- PHP 8.1+
- Laravel 10.x or 11.x
- MySQL 8.0+ / PostgreSQL 13+
- GD or Imagick extension (for thumbnails)

---

## Step 1 — Install via Composer

```bash
composer require ranitachi/filemanager-laravel
```

---

## Step 2 — Publish Assets

```bash
# Publish everything at once
php artisan vendor:publish --tag=filemanager

# Or selectively:
php artisan vendor:publish --tag=filemanager-config
php artisan vendor:publish --tag=filemanager-migrations
php artisan vendor:publish --tag=filemanager-assets
php artisan vendor:publish --tag=filemanager-views
```

---

## Step 3 — Run Migrations

```bash
php artisan migrate
```

---

## Step 4 — Configure `.env`

```env
# Storage driver: local | s3 | minio
FILEMANAGER_DISK=local
FILEMANAGER_MAX_SIZE=10240
FILEMANAGER_SIGNED_URL_EXPIRE=60

# For S3:
FILEMANAGER_ADAPTER=Ranitachi\FileManager\Storage\S3Adapter
FILEMANAGER_S3_KEY=your-key
FILEMANAGER_S3_SECRET=your-secret
FILEMANAGER_S3_REGION=ap-southeast-1
FILEMANAGER_S3_BUCKET=your-bucket

# For MinIO (same as S3 + endpoint):
FILEMANAGER_ADAPTER=Ranitachi\FileManager\Storage\S3Adapter
FILEMANAGER_S3_ENDPOINT=http://minio:9000
FILEMANAGER_S3_BUCKET=your-bucket

# Thumbnails
FILEMANAGER_THUMBS=true
FILEMANAGER_IMAGE_DRIVER=gd

# Optional ClamAV antivirus
FILEMANAGER_AV_ENABLED=false
FILEMANAGER_AV_HOST=localhost
FILEMANAGER_AV_PORT=3310
```

---

## Step 5 — Ensure Storage Link (local)

```bash
php artisan storage:link
```

---

## Step 6 — Auth Setup

The package uses `auth:sanctum` for API routes by default. Ensure you have
Sanctum installed, or change the middleware in `config/filemanager.php`:

```php
'middleware' => [
    'web' => ['web', 'auth'],
    'api' => ['api', 'auth:sanctum'],   // change to 'auth:api' for Passport
],
```

---

## API Quick Test

```bash
# Get Bearer token (via Sanctum)
TOKEN="your-sanctum-token"

# Upload a file
curl -X POST http://localhost/api/v1/filemanager/upload \
  -H "Authorization: Bearer $TOKEN" \
  -F "file=@/path/to/document.pdf"

# Browse files
curl http://localhost/api/v1/filemanager/files \
  -H "Authorization: Bearer $TOKEN"

# Create folder
curl -X POST http://localhost/api/v1/filemanager/folders \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"name":"My Documents"}'
```

---

## WYSIWYG Integration

### CKEditor 5
```javascript
import { FileManagerPlugin } from '/vendor/filemanager/js/ckeditor-plugin.js';

ClassicEditor.create(document.querySelector('#editor'), {
    extraPlugins: [FileManagerPlugin],
    fileManager: {
        pickerUrl: '/filemanager/picker',
    }
});
```

### TinyMCE
```javascript
tinymce.init({
    selector: '#editor',
    file_picker_callback: function(callback, value, meta) {
        const cbName = 'tmce_' + Date.now();
        window[cbName] = (url, name) => { callback(url, { alt: name }); delete window[cbName]; };
        window.open(`/filemanager/picker?callback=${cbName}&type=${meta.filetype}`, '_blank', 'width=900,height=600');
    }
});
```

### Summernote
```javascript
$('#editor').summernote({
    buttons: {
        filemanager: summernoteFileManagerButton('/filemanager/picker')
    }
});
```

---

## Using the Facade

```php
use Ranitachi\FileManager\Facades\FileManager;

// Upload
$file = FileManager::upload($request->file('document'), $folderId);

// Download
return FileManager::download($file->id);

// Browse
$files = FileManager::browse($folderId, ['per_page' => 20, 'sort' => 'name']);
```

---

## Running Tests

```bash
composer test
```
