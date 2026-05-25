# Secure File Manager Blueprint

**Laravel 10+ / Goravel Ready — API First — WYSIWYG Integration — Package Ready**

Dokumen ini merupakan blueprint teknis untuk membangun sistem **File Manager modern, secure, modular, extensible**, dan siap dikembangkan menjadi package open-source Packagist. Sistem ini dirancang sebagai pengganti Laravel File Manager lama seperti `unisharp/laravel-filemanager` yang memiliki keterbatasan dari sisi keamanan, RBAC, scalability, dan integrasi editor.

---

## 0. Ringkasan Tujuan

Sistem ini dirancang dengan prinsip:

- Secure by design
- API-first
- Modular architecture
- Bisa standalone tanpa wajib bergantung penuh pada database Laravel
- Bisa menggunakan metadata database jika dibutuhkan
- Mendukung Local Storage, S3, dan MinIO
- Mudah diintegrasikan dengan CKEditor, TinyMCE, dan Summernote
- Siap dikembangkan menjadi package Packagist

Nama sementara:

```txt
SecureFileManager
```

Namespace Laravel module:

```txt
Modules/FileManager
```

Namespace package:

```txt
Fachran\SecureFileManager
```

---

# 1. Business & System Analysis

## 1.1 Pain Point Existing Laravel File Manager

Beberapa masalah umum pada Laravel File Manager lama:

### 1. Security Issue

- Rentan terhadap path traversal seperti `../../.env`.
- Akses file sering hanya berdasarkan URL tanpa authorization yang memadai.
- Upload file berbahaya dapat lolos jika validasi hanya berdasarkan extension.
- Public folder terlalu terbuka.
- Tidak semua file private dilindungi signed URL.

### 2. RBAC Lemah

- Sulit membatasi folder berdasarkan role.
- Sulit membatasi aksi granular seperti upload, rename, delete, move, share.
- Tidak mendukung ownership per user, OPD, unit kerja, atau aplikasi.
- Tidak fleksibel untuk kebutuhan e-Government multi-role.

### 3. Tidak Enterprise Ready

- Tidak tersedia audit log detail.
- Sulit dikembangkan untuk multi aplikasi.
- Tidak mudah diintegrasikan dengan MinIO/S3.
- Tidak memiliki API yang konsisten dan documented.

### 4. Integrasi Editor Terlalu Vendor-Specific

- Biasanya hanya cocok untuk CKEditor versi tertentu.
- Callback tidak standar.
- Sulit digunakan ulang untuk TinyMCE atau Summernote.

### 5. Sulit Dijadikan Package Modern

- Banyak hardcode path.
- Backend dan UI terlalu menyatu.
- Sulit publish config, migration, assets, dan event.

---

## 1.2 Aktor Utama

### Admin

Admin memiliki akses penuh untuk:

- Melihat seluruh folder dan file.
- Membuat folder.
- Upload file.
- Rename file/folder.
- Delete file/folder.
- Mengatur permission.
- Melihat audit log.

### Operator

Operator memiliki akses terbatas pada folder tertentu, misalnya:

```txt
/opd/diskominfo/
/opd/bkpsdm/
/layanan/simpati/
```

Aksi yang umum diizinkan:

- Upload file.
- Browse folder yang diizinkan.
- Preview file.
- Rename file miliknya sendiri.
- Delete file jika memiliki permission.

### Public User

Public user biasanya hanya dapat:

- Upload file ke folder tertentu.
- Melihat file miliknya sendiri.
- Tidak bisa browse seluruh storage.
- Tidak bisa delete file publik tanpa otorisasi.

---

## 1.3 Functional Requirement

| Fitur | Deskripsi |
|---|---|
| Browse File | Melihat daftar file/folder berdasarkan folder aktif |
| Upload File | Upload single/multiple file |
| Drag & Drop Upload | Upload melalui UI modern |
| Create Folder | Membuat folder baru |
| Rename | Rename file/folder |
| Delete | Soft delete metadata atau hard delete storage |
| Move | Memindahkan file/folder |
| Copy | Menyalin file |
| Preview | Preview image, PDF, text, video, audio |
| Search | Cari file berdasarkan nama, tipe, owner |
| Sort | Sort by name, size, date, type |
| Filter | Filter berdasarkan mime, extension, owner |
| Download | Download file dengan authorization |
| Temporary URL | URL sementara untuk file private |
| Editor Picker | Pilih file lalu insert ke editor |
| Audit Log | Catat semua aktivitas file |
| RBAC | Kontrol akses berdasarkan role/permission |
| Storage Adapter | Local, S3, MinIO |

---

## 1.4 Non-Functional Requirement

| Aspek | Requirement |
|---|---|
| Security | Validasi file, path normalization, RBAC, signed URL |
| Performance | Pagination, lazy folder tree, chunk upload opsional |
| Scalability | Support object storage seperti S3/MinIO |
| Maintainability | Clean architecture, service-repository-adapter |
| Observability | Audit log, error log, event hooks |
| Compatibility | Laravel 10+, PHP 8.1+, SPA ready |
| Extensibility | Config file, event, contract/interface |
| Integration | CKEditor, TinyMCE, Summernote |
| Government Ready | Mendukung multi OPD, unit kerja, user ownership |

---

# 2. System Architecture

## 2.1 Struktur Modular Laravel

```txt
app/
└── Modules/
    └── FileManager/
        ├── Controllers/
        │   ├── FileController.php
        │   ├── FolderController.php
        │   └── PickerController.php
        ├── Requests/
        │   ├── UploadFileRequest.php
        │   ├── CreateFolderRequest.php
        │   └── RenameFileRequest.php
        ├── Services/
        │   ├── FileManagerService.php
        │   ├── FilePermissionService.php
        │   └── FileUrlService.php
        ├── Repositories/
        │   ├── FileRepository.php
        │   ├── FolderRepository.php
        │   └── FileLogRepository.php
        ├── Storage/
        │   ├── Contracts/
        │   │   └── StorageAdapterInterface.php
        │   ├── LocalStorageAdapter.php
        │   ├── S3StorageAdapter.php
        │   └── MinioStorageAdapter.php
        ├── DTO/
        │   ├── UploadFileData.php
        │   └── FileItemData.php
        ├── Policies/
        │   └── FilePolicy.php
        ├── Events/
        │   ├── BeforeUpload.php
        │   ├── AfterUpload.php
        │   └── BeforeDelete.php
        └── routes.php
```

---

## 2.2 Diagram Arsitektur Textual

```txt
[User / Editor / SPA]
        |
        v
[REST API Controller]
        |
        v
[Request Validation]
        |
        v
[RBAC / Policy Check]
        |
        v
[FileManagerService]
        |
        +--------------------+
        |                    |
        v                    v
[FileRepository]     [StorageAdapterInterface]
        |                    |
        v                    v
[Database Metadata]  [Local / S3 / MinIO Storage]
        |
        v
[Audit Log / Events]
```

---

## 2.3 Layer Responsibility

### Controller

Tugas:

- Menerima HTTP request.
- Memanggil FormRequest.
- Memanggil service.
- Mengembalikan JSON response.

Tidak boleh:

- Memproses path langsung.
- Akses storage langsung.
- Memuat business logic besar.

### Service

Tugas:

- Business logic.
- Validasi permission.
- Normalisasi path.
- Memanggil storage adapter.
- Memanggil repository.
- Trigger event.

### Repository

Tugas:

- Query metadata file/folder.
- Simpan audit log.
- Query permission.

### Storage Adapter

Tugas:

- Upload file fisik.
- Delete file fisik.
- Generate temporary URL.
- Cek file exists.
- List object jika mode tanpa database.

---

## 2.4 Flow Upload File

```txt
User pilih file
    |
    v
POST /api/file-manager/upload
    |
    v
Auth Bearer/JWT
    |
    v
Validate request:
- file required
- max size
- mime allowed
- folder_id/path valid
    |
    v
Check RBAC:
- can upload?
- can access folder?
    |
    v
Normalize target path
    |
    v
Scan optional antivirus
    |
    v
Upload via StorageAdapter
    |
    v
Save metadata ke files table
    |
    v
Write audit log
    |
    v
Return JSON file info
```

---

## 2.5 Flow Browse File

```txt
User buka File Manager
    |
    v
GET /api/file-manager/files?folder_id=xxx
    |
    v
Auth Check
    |
    v
Permission Check Folder
    |
    v
Query folder + files
    |
    v
Apply pagination, search, filter
    |
    v
Return JSON list
```

---

## 2.6 Storage Interface

```php
<?php

namespace App\Modules\FileManager\Storage\Contracts;

use Illuminate\Http\UploadedFile;

interface StorageAdapterInterface
{
    public function put(string $path, UploadedFile $file, array $options = []): string;

    public function delete(string $path): bool;

    public function exists(string $path): bool;

    public function url(string $path): string;

    public function temporaryUrl(string $path, int $minutes = 15): string;

    public function move(string $from, string $to): bool;

    public function copy(string $from, string $to): bool;
}
```

---

# 3. Database Design

Database tidak wajib untuk mode minimal. Namun untuk enterprise/e-Government, metadata database sangat disarankan karena mendukung:

- RBAC.
- Ownership.
- Audit log.
- Search.
- File versioning.
- Integrasi multi sistem.

---

## 3.1 ERD Text-Based

```txt
users
  └── files.created_by
  └── folders.created_by
  └── file_logs.user_id

folders
  ├── id
  ├── parent_id self reference
  ├── name
  ├── path
  ├── disk
  ├── visibility
  └── created_by

files
  ├── id
  ├── folder_id
  ├── original_name
  ├── stored_name
  ├── path
  ├── disk
  ├── mime_type
  ├── extension
  ├── size
  ├── checksum
  ├── visibility
  └── created_by

file_permissions
  ├── id
  ├── subject_type
  ├── subject_id
  ├── resource_type
  ├── resource_id
  ├── permission
  └── effect

file_logs
  ├── id
  ├── user_id
  ├── action
  ├── resource_type
  ├── resource_id
  ├── ip_address
  ├── user_agent
  └── meta
```

---

## 3.2 Relasi Antar Tabel

```txt
fm_folders.parent_id -> fm_folders.id
fm_files.folder_id -> fm_folders.id
fm_files.created_by -> users.id
fm_folders.created_by -> users.id
fm_file_logs.user_id -> users.id
fm_file_permissions.resource_id -> fm_files.id / fm_folders.id
```

---

## 3.3 Migration: fm_folders

```php
Schema::create('fm_folders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('parent_id')->nullable()->constrained('fm_folders')->nullOnDelete();

    $table->string('name');
    $table->string('slug')->nullable();
    $table->string('path')->index();

    $table->string('disk')->default('local');
    $table->enum('visibility', ['private', 'public'])->default('private');

    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

    $table->json('meta')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['disk', 'path']);
});
```

---

## 3.4 Migration: fm_files

```php
Schema::create('fm_files', function (Blueprint $table) {
    $table->id();

    $table->foreignId('folder_id')->nullable()->constrained('fm_folders')->nullOnDelete();

    $table->string('original_name');
    $table->string('stored_name');
    $table->string('path')->index();

    $table->string('disk')->default('local');
    $table->string('mime_type')->nullable();
    $table->string('extension', 20)->nullable();

    $table->unsignedBigInteger('size')->default(0);
    $table->string('checksum')->nullable();

    $table->enum('visibility', ['private', 'public'])->default('private');
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

    $table->json('meta')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->unique(['disk', 'path']);
});
```

---

## 3.5 Migration: fm_file_permissions

```php
Schema::create('fm_file_permissions', function (Blueprint $table) {
    $table->id();

    $table->string('subject_type');
    $table->unsignedBigInteger('subject_id');

    $table->enum('resource_type', ['file', 'folder']);
    $table->unsignedBigInteger('resource_id');

    $table->enum('permission', [
        'view',
        'upload',
        'download',
        'rename',
        'delete',
        'move',
        'share',
        'manage'
    ]);

    $table->enum('effect', ['allow', 'deny'])->default('allow');

    $table->timestamps();

    $table->index(['subject_type', 'subject_id']);
    $table->index(['resource_type', 'resource_id']);
});
```

---

## 3.6 Migration: fm_file_logs

```php
Schema::create('fm_file_logs', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

    $table->string('action');
    $table->enum('resource_type', ['file', 'folder'])->nullable();
    $table->unsignedBigInteger('resource_id')->nullable();

    $table->string('ip_address')->nullable();
    $table->text('user_agent')->nullable();

    $table->json('meta')->nullable();

    $table->timestamps();

    $table->index(['action', 'resource_type', 'resource_id']);
});
```

---

# 4. Security Design

## 4.1 Path Traversal Prevention

Jangan pernah menerima path mentah dari user lalu langsung dipakai ke storage.

Contoh input berbahaya:

```txt
../../.env
../../../storage/logs/laravel.log
public/../../config/app.php
```

Gunakan normalizer:

```php
<?php

final class PathSanitizer
{
    public static function normalize(string $path): string
    {
        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/+#', '/', $path);
        $path = trim($path, '/');

        if (str_contains($path, '..')) {
            throw new InvalidArgumentException('Invalid path.');
        }

        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new InvalidArgumentException('Invalid path character.');
        }

        return $path;
    }

    public static function join(string ...$segments): string
    {
        $path = implode('/', array_filter($segments));
        return self::normalize($path);
    }
}
```

Gunakan folder root terkontrol:

```php
$basePath = config('filemanager.base_path', 'uploads');
$targetPath = PathSanitizer::join($basePath, $folderPath, $storedName);
```

---

## 4.2 File Validation

Validasi minimal:

```php
return [
    'folder_id' => ['nullable', 'integer'],
    'file' => [
        'required',
        'file',
        'max:' . config('filemanager.max_size_kb', 10240),
        'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv'
    ],
];
```

Tambahan validasi MIME:

```php
$allowedMime = config('filemanager.allowed_mime_types');

if (! in_array($file->getMimeType(), $allowedMime, true)) {
    throw ValidationException::withMessages([
        'file' => 'File type not allowed.'
    ]);
}
```

Jangan hanya percaya pada:

```php
$file->getClientOriginalExtension();
```

Gunakan kombinasi:

```php
$mime = $file->getMimeType();
$extension = strtolower($file->extension());
```

---

## 4.3 Block Dangerous Extension

```php
'dangerous_extensions' => [
    'php',
    'phtml',
    'phar',
    'exe',
    'sh',
    'bat',
    'cmd',
    'js',
    'html',
    'svg',
]
```

SVG sebaiknya diblokir kecuali sudah ada sanitizer khusus, karena dapat membawa XSS.

---

## 4.4 Role-Based Access Control

Permission granular:

```txt
filemanager.view
filemanager.upload
filemanager.download
filemanager.rename
filemanager.delete
filemanager.move
filemanager.share
filemanager.manage
```

Contoh Policy:

```php
public function upload(User $user, Folder $folder): bool
{
    if ($user->hasRole('SuperAdmin')) {
        return true;
    }

    return $user->can('filemanager.upload')
        && $this->userCanAccessFolder($user, $folder);
}
```

---

## 4.5 Signed URL / Temporary URL

Untuk file private, jangan expose path asli.

Endpoint:

```txt
GET /api/file-manager/files/{id}/temporary-url
```

Response:

```json
{
  "success": true,
  "data": {
    "url": "https://domain.test/file-manager/preview/signed-token",
    "expires_at": "2026-04-28T11:15:00+07:00"
  }
}
```

Laravel signed route:

```php
URL::temporarySignedRoute(
    'filemanager.preview',
    now()->addMinutes(15),
    ['file' => $file->id]
);
```

---

## 4.6 Rate Limiting Upload

Route:

```php
Route::middleware(['auth:sanctum', 'throttle:file-upload'])
    ->post('/upload', [FileController::class, 'upload']);
```

Provider:

```php
RateLimiter::for('file-upload', function ($request) {
    return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
});
```

---

## 4.7 Antivirus Hook Optional

Untuk enterprise:

```php
interface AntivirusScannerInterface
{
    public function scan(string $realPath): bool;
}
```

Contoh service:

```php
if (config('filemanager.antivirus.enabled')) {
    $clean = app(AntivirusScannerInterface::class)->scan($file->getRealPath());

    if (! $clean) {
        throw ValidationException::withMessages([
            'file' => 'File failed security scan.'
        ]);
    }
}
```

---

# 5. API Design RESTful

Base URL:

```txt
/api/file-manager
```

Auth:

```txt
Authorization: Bearer {token}
```

---

## 5.1 GET /files

Request:

```txt
GET /api/file-manager/files?folder_id=1&search=laporan&type=image&page=1&per_page=24
```

Response:

```json
{
  "success": true,
  "message": "Files loaded successfully.",
  "data": {
    "folder": {
      "id": 1,
      "name": "Dokumen",
      "path": "uploads/dokumen"
    },
    "items": [
      {
        "id": 10,
        "type": "file",
        "name": "surat.pdf",
        "mime_type": "application/pdf",
        "extension": "pdf",
        "size": 204800,
        "url": "/api/file-manager/preview?id=10",
        "created_at": "2026-04-28 10:00:00"
      }
    ]
  }
}
```

---

## 5.2 POST /upload

Request multipart:

```txt
POST /api/file-manager/upload
Content-Type: multipart/form-data

folder_id: 1
file: document.pdf
visibility: private
```

Response:

```json
{
  "success": true,
  "message": "File uploaded successfully.",
  "data": {
    "id": 15,
    "name": "document.pdf",
    "mime_type": "application/pdf",
    "extension": "pdf",
    "size": 345000,
    "url": "/api/file-manager/preview?id=15"
  }
}
```

---

## 5.3 DELETE /file/{id}

Request:

```txt
DELETE /api/file-manager/file/15
```

Response:

```json
{
  "success": true,
  "message": "File deleted successfully."
}
```

---

## 5.4 POST /folder

Request:

```json
{
  "parent_id": 1,
  "name": "Laporan 2026"
}
```

Response:

```json
{
  "success": true,
  "message": "Folder created successfully.",
  "data": {
    "id": 20,
    "name": "Laporan 2026",
    "path": "uploads/dokumen/laporan-2026"
  }
}
```

---

## 5.5 GET /preview

Request:

```txt
GET /api/file-manager/preview?id=15
```

Response:

- image/pdf: stream response
- private file: harus melalui auth atau signed URL

---

## 5.6 Route Laravel

```php
Route::prefix('api/file-manager')
    ->middleware(['auth:sanctum'])
    ->group(function () {
        Route::get('/files', [FileController::class, 'index']);
        Route::post('/upload', [FileController::class, 'upload'])->middleware('throttle:file-upload');
        Route::delete('/file/{file}', [FileController::class, 'destroy']);
        Route::post('/folder', [FolderController::class, 'store']);
        Route::get('/preview', [FileController::class, 'preview']);
        Route::get('/picker', [PickerController::class, 'index']);
    });
```

---

# 6. UI/UX Flow

## 6.1 Layout Utama

```txt
+------------------------------------------------------+
| Header: Search | Upload Button | View Toggle | User  |
+----------------------+-------------------------------+
| Sidebar Folder Tree  | Toolbar                       |
|                      | Breadcrumb                    |
| - Root               |                               |
| - Dokumen            | +-------------------------+   |
| - Gambar             | | File Grid / List         |   |
| - OPD                | |                         |   |
|   - BKPSDM           | | [img] [pdf] [doc]       |   |
|   - Diskominfo       | |                         |   |
+----------------------+-------------------------------+
| Status bar: selected file, size, permission           |
+------------------------------------------------------+
```

---

## 6.2 Sidebar Folder Tree

Fitur:

- Lazy load folder.
- Expand/collapse.
- Right click folder.
- Create subfolder.
- Rename folder.
- Delete folder jika kosong.

State:

```txt
Loading folder...
No folder found.
Failed to load folder.
```

---

## 6.3 File Grid/List

Mode grid:

```txt
+---------+ +---------+ +---------+
|  IMG    | |  PDF    | |  DOC    |
| a.jpg   | | b.pdf   | | c.docx  |
+---------+ +---------+ +---------+
```

Mode list:

```txt
Name              Type       Size       Date
------------------------------------------------
surat.pdf         PDF        200 KB     28 Apr 2026
foto.jpg          Image      1.2 MB     28 Apr 2026
```

---

## 6.4 Upload Drag & Drop

Flow:

```txt
User drag file
    |
Drop area aktif
    |
Validasi extension & size di frontend
    |
Upload progress bar
    |
Jika sukses: refresh list
Jika gagal: tampilkan error
```

UI state:

```txt
[Drop files here or click to upload]

Uploading...
[=====>       ] 45%

Upload success.
Upload failed: file type not allowed.
```

---

## 6.5 Preview Panel

| File Type | Preview |
|---|---|
| Image | Tampilkan image |
| PDF | iframe/pdf viewer |
| Video | HTML5 video |
| Audio | HTML5 audio |
| Text | Plain text viewer |
| Office | Download / optional office preview |
| Unknown | Icon + download |

---

## 6.6 Context Menu

Right click file:

```txt
Open Preview
Copy URL
Rename
Download
Move
Delete
Properties
```

Right click folder:

```txt
Open
New Folder
Rename
Delete
Properties
```

---

## 6.7 Flow User Step-by-Step

### Browse

```txt
1. User membuka File Manager.
2. Sistem load root folder.
3. User klik folder.
4. Sistem mengambil daftar file via GET /files.
5. File tampil dalam grid/list.
```

### Upload

```txt
1. User klik Upload atau drag file.
2. Sistem validasi ukuran dan tipe.
3. Sistem kirim POST /upload.
4. Progress upload tampil.
5. File baru muncul di list.
6. Audit log tersimpan.
```

### Insert ke Editor

```txt
1. User klik tombol browse di editor.
2. Popup File Manager terbuka.
3. User pilih file.
4. User klik Select.
5. File URL dikirim ke editor callback.
6. Editor menyisipkan image/link.
```

---

# 7. Integrasi WYSIWYG Editor

## 7.1 Flow Umum Integrasi

```txt
Editor klik browse
    |
Open popup:
/file-manager/picker?editor=ckeditor&callback=...
    |
User pilih file
    |
File Manager return selected file:
{
  url,
  name,
  mime_type
}
    |
Editor insert image/link
```

---

## 7.2 Callback Response Standar

```json
{
  "success": true,
  "data": {
    "url": "https://domain.test/storage/uploads/image.jpg",
    "name": "image.jpg",
    "mime_type": "image/jpeg"
  }
}
```

---

## 7.3 CKEditor 5 Integration

```js
class SecureFileManagerUploadAdapter {
    constructor(loader) {
        this.loader = loader;
    }

    upload() {
        return this.loader.file.then(file => {
            const data = new FormData();
            data.append('file', file);
            data.append('folder_id', window.fileManagerFolderId || '');

            return fetch('/api/file-manager/upload', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + window.authToken,
                    'Accept': 'application/json'
                },
                body: data
            })
            .then(response => response.json())
            .then(result => {
                return {
                    default: result.data.url
                };
            });
        });
    }

    abort() {}
}

function SecureFileManagerPlugin(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = loader => {
        return new SecureFileManagerUploadAdapter(loader);
    };
}

ClassicEditor
    .create(document.querySelector('#editor'), {
        extraPlugins: [SecureFileManagerPlugin]
    });
```

---

## 7.4 TinyMCE Integration

```js
tinymce.init({
    selector: '#editor',
    plugins: 'image link media code',
    toolbar: 'undo redo | bold italic | image link media | code',

    file_picker_callback: function (callback, value, meta) {
        const width = 900;
        const height = 600;

        const url = '/file-manager/picker?editor=tinymce&type=' + meta.filetype;

        const win = window.open(
            url,
            'SecureFileManager',
            `width=${width},height=${height}`
        );

        window.SetUrl = function (file) {
            callback(file.url, {
                text: file.name,
                title: file.name
            });

            win.close();
        };
    }
});
```

Picker select button:

```js
function selectFile(file) {
    if (window.opener && window.opener.SetUrl) {
        window.opener.SetUrl({
            url: file.url,
            name: file.name,
            mime_type: file.mime_type
        });
    }

    window.close();
}
```

---

## 7.5 Summernote Integration

```js
$('#editor').summernote({
    height: 300,
    callbacks: {
        onImageUpload: function(files) {
            uploadSummernoteImage(files[0], this);
        }
    },
    toolbar: [
        ['insert', ['picture', 'link']],
        ['style', ['bold', 'italic', 'underline']],
        ['view', ['codeview']]
    ]
});

function uploadSummernoteImage(file, editor) {
    const data = new FormData();
    data.append('file', file);

    fetch('/api/file-manager/upload', {
        method: 'POST',
        headers: {
            'Authorization': 'Bearer ' + window.authToken,
            'Accept': 'application/json'
        },
        body: data
    })
    .then(response => response.json())
    .then(result => {
        $(editor).summernote('insertImage', result.data.url, result.data.name);
    });
}
```

---

# 8. Extensibility untuk Package

## 8.1 Config filemanager.php

```php
return [

    'disk' => env('FILEMANAGER_DISK', 'local'),

    'base_path' => env('FILEMANAGER_BASE_PATH', 'uploads'),

    'max_size_kb' => env('FILEMANAGER_MAX_SIZE_KB', 10240),

    'allowed_mime_types' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],

    'dangerous_extensions' => [
        'php',
        'phtml',
        'phar',
        'exe',
        'sh',
        'bat',
        'cmd',
        'js',
        'html',
        'svg',
    ],

    'features' => [
        'database_metadata' => true,
        'audit_log' => true,
        'temporary_url' => true,
        'antivirus' => false,
        'image_optimization' => false,
    ],

    'routes' => [
        'prefix' => 'api/file-manager',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    'ui' => [
        'enabled' => true,
        'route' => 'file-manager',
        'middleware' => ['web', 'auth'],
    ],

];
```

---

## 8.2 Service Provider Package

```php
class FileManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/filemanager.php',
            'filemanager'
        );

        $this->app->bind(
            StorageAdapterInterface::class,
            fn () => match (config('filemanager.disk')) {
                's3' => new S3StorageAdapter(),
                'minio' => new MinioStorageAdapter(),
                default => new LocalStorageAdapter(),
            }
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filemanager');

        $this->publishes([
            __DIR__.'/../config/filemanager.php' => config_path('filemanager.php'),
        ], 'filemanager-config');

        $this->publishes([
            __DIR__.'/../public' => public_path('vendor/filemanager'),
        ], 'filemanager-assets');
    }
}
```

---

## 8.3 Event Hook

```php
event(new BeforeUpload($user, $file, $folder));

$uploaded = $this->storage->put($targetPath, $file);

event(new AfterUpload($user, $uploadedFile));
```

Event yang direkomendasikan:

```txt
BeforeUpload
AfterUpload
BeforeDelete
AfterDelete
BeforeRename
AfterRename
BeforeMove
AfterMove
```

---

# 9. Future Roadmap

## Phase 1 — Internal MVP

- API upload.
- Browse folder.
- Delete file.
- Preview file.
- RBAC basic.
- Audit log.
- CKEditor integration.

## Phase 2 — UI Stable

- Sidebar tree.
- Drag & drop upload.
- Grid/list mode.
- Context menu.
- TinyMCE dan Summernote support.

## Phase 3 — Enterprise

- MinIO/S3 support.
- Signed URL.
- Rate limit.
- Antivirus hook.
- File versioning.

## Phase 4 — Package

- Publish config.
- Publish assets.
- Package documentation.
- Test suite.
- Semantic versioning.

## Phase 5 — Advanced

- CDN integration.
- Image resize/compress.
- AI tagging.
- Duplicate detection.
- Public share link.
- Expiring share link.
- Folder quota.
- Multi-tenant storage.

---

# 10. Rekomendasi Final Arsitektur

Untuk kebutuhan pemerintahan dan multi aplikasi, pendekatan terbaik adalah:

```txt
API First + Metadata Database Optional + Storage Adapter + RBAC Policy
```

Mode penggunaan:

```txt
1. Standalone internal module Laravel
2. Headless API untuk SPA
3. Picker untuk WYSIWYG
4. Package Packagist jangka panjang
```

Struktur package ideal:

```txt
secure-filemanager/
├── config/
│   └── filemanager.php
├── database/
│   └── migrations/
├── routes/
│   ├── api.php
│   └── web.php
├── src/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Storage/
│   ├── DTO/
│   ├── Events/
│   └── FileManagerServiceProvider.php
├── resources/
│   ├── views/
│   └── js/
├── public/
│   └── vendor/filemanager/
└── README.md
```

---

# 11. Prompt Lanjutan untuk Agent AI

```txt
Anda adalah Senior Laravel Package Developer.

Bangun package Laravel 10+ bernama secure-filemanager dengan pendekatan API-first, clean architecture, storage adapter, RBAC, dan WYSIWYG integration.

Requirement utama:
1. Namespace package: Fachran\SecureFileManager
2. Config publishable: config/filemanager.php
3. Routes:
   - GET /api/file-manager/files
   - POST /api/file-manager/upload
   - DELETE /api/file-manager/file/{id}
   - POST /api/file-manager/folder
   - GET /api/file-manager/preview
4. Buat migration:
   - fm_folders
   - fm_files
   - fm_file_permissions
   - fm_file_logs
5. Buat layer:
   - Controller
   - FormRequest
   - Service
   - Repository
   - StorageAdapterInterface
   - LocalStorageAdapter
   - MinioStorageAdapter
6. Terapkan security:
   - path traversal prevention
   - mime validation
   - dangerous extension block
   - signed URL
   - upload rate limit
7. Buat UI picker sederhana untuk integrasi:
   - CKEditor
   - TinyMCE
   - Summernote
8. Buat README.md lengkap:
   - instalasi
   - publish config
   - migration
   - route
   - contoh integrasi editor
9. Gunakan Laravel best practice, PHP 8.1+, strict typing, dan clean architecture.
10. Hindari hardcode path. Semua harus configurable.
```

---

# 12. Catatan Implementasi Internal Project

Untuk kebutuhan internal project e-Government, implementasi awal sebaiknya tidak langsung dibuat terlalu berat seperti package penuh. Disarankan dimulai dari module internal:

```txt
app/Modules/FileManager
```

Kemudian setelah stabil, baru diekstraksi menjadi package:

```txt
packages/ranitachi/filemanager-laravel
```

Tahapan praktis:

```txt
1. Buat module internal.
2. Buat API upload dan browse.
3. Buat metadata table.
4. Terapkan RBAC.
5. Buat UI picker sederhana.
6. Integrasikan ke CKEditor.
7. Tambahkan TinyMCE/Summernote.
8. Refactor menjadi package.
9. Publish ke repository private/public.
10. Publish Packagist jika sudah stabil.
```

---

# 13. Kesimpulan

Secure File Manager ini dirancang untuk menggantikan file manager lama dengan pendekatan yang lebih aman, modular, scalable, dan mudah diintegrasikan. Sistem ini cocok untuk:

1. Project internal Laravel/Goravel.
2. Sistem pemerintahan multi OPD.
3. Portal publik.
4. Backoffice CMS.
5. Integrasi WYSIWYG editor.
6. Pengembangan package jangka panjang.

Blueprint ini dapat langsung digunakan sebagai dasar:

- dokumen teknis,
- README package,
- acuan development sprint,
- prompt agent AI lanjutan,
- atau TOR teknis pengembangan File Manager secure.
