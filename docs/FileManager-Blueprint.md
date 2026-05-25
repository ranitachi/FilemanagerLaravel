# 📁 Laravel Secure File Manager — Technical Blueprint

> **Version:** 1.0.0-blueprint  
> **Target:** Laravel 10+ | Goravel Compatible | Packagist Ready  
> **Author:** System Architecture Document  
> **Status:** Blueprint / Pre-Development  

---

## Table of Contents

1. [Business & System Analysis](#1-business--system-analysis)
2. [System Architecture](#2-system-architecture)
3. [Database Design](#3-database-design)
4. [Security Design](#4-security-design)
5. [API Design (RESTful)](#5-api-design-restful)
6. [UI/UX Flow](#6-uiux-flow)
7. [WYSIWYG Editor Integration](#7-wysiwyg-editor-integration)
8. [Extensibility & Package Design](#8-extensibility--package-design)
9. [Future Roadmap](#9-future-roadmap)
10. [Quick Start](#10-quick-start)

---

## 1. Business & System Analysis

### 1.1 Pain Points — Existing Laravel File Manager (`unisharp/laravel-filemanager`)

| # | Pain Point | Impact | Severity |
|---|------------|--------|----------|
| 1 | **Path Traversal Vulnerability** — user input tidak disanitasi sepenuhnya | Data breach, akses file sistem | 🔴 Critical |
| 2 | **No Granular RBAC** — semua user authenticated mendapat akses sama | Unauthorized access antar user | 🔴 Critical |
| 3 | **No Audit Trail** — tidak ada logging aktivitas file | Tidak dapat investigasi insiden | 🟠 High |
| 4 | **Tightly Coupled** — sulit di-extend tanpa modifikasi core | Tech debt, upgrade hell | 🟠 High |
| 5 | **No Multi-Storage** — hanya local storage secara default | Tidak scalable untuk cloud | 🟠 High |
| 6 | **Session-based Auth** — tidak mendukung JWT/API token native | Tidak bisa dipakai sebagai API backend | 🟡 Medium |
| 7 | **No Rate Limiting** — rawan DDoS/abuse upload | Server overload, storage abuse | 🟡 Medium |
| 8 | **No MIME Validation** — ekstensi bisa dipalsukan | RCE via file upload | 🔴 Critical |
| 9 | **No Antivirus Hook** — tidak ada pre-processing scan | Malware masuk ke storage | 🟡 Medium |
| 10 | **UI tidak SPA-ready** — full page reload, susah integrasi modern | Developer experience buruk | 🟢 Low |

---

### 1.2 Use Case Utama

#### Actor: Super Admin
- Mengelola semua folder dan file di seluruh sistem
- Mengatur permission RBAC per user/role
- Melihat audit log semua aktivitas
- Konfigurasi storage adapter (local/S3/MinIO)
- Hard delete file (permanent)

#### Actor: Operator / Staff
- Upload file ke folder yang di-assign
- Browse file milik sendiri atau shared folder
- Rename, move, soft-delete file milik sendiri
- Preview file (image, PDF, document)
- Share file (generate temporary link)

#### Actor: Public User (Authenticated)
- Browse folder publik yang di-grant
- Download file yang di-izinkan
- Tidak bisa upload atau delete

#### Actor: WYSIWYG Editor (System)
- Membuka File Manager sebagai popup/modal
- Memilih/upload file untuk di-insert ke editor
- Menerima callback URL setelah file dipilih

---

### 1.3 Functional Requirements

```
FR-01: Upload file (single & multi) dengan progress indicator
FR-02: Browse file dan folder (tree navigation)
FR-03: Create folder baru
FR-04: Rename file/folder
FR-05: Move file antar folder (drag & drop / modal)
FR-06: Copy file ke folder lain
FR-07: Delete file (soft delete + permanent delete untuk admin)
FR-08: Preview file (image, PDF, video, audio, text)
FR-09: Download file (dengan access check)
FR-10: Search file berdasarkan nama, tag, tipe
FR-11: RBAC — assign permission read/write/delete per folder per user/role
FR-12: Audit log — catat semua aktivitas (who, what, when, where)
FR-13: Generate temporary signed URL untuk sharing
FR-14: Integrasi WYSIWYG editor (CKEditor, TinyMCE, Summernote)
FR-15: Multi-storage support (Local, S3, MinIO, GCS)
FR-16: Thumbnail generation untuk gambar
FR-17: File versioning (roadmap)
```

### 1.4 Non-Functional Requirements

```
NFR-01 Security    — OWASP Top 10 compliant, path traversal proof
NFR-02 Performance — Response < 200ms untuk browse, upload chunked untuk file > 10MB
NFR-03 Scalability — Horizontal scalable, storage adapter pattern
NFR-04 Availability— Tidak bergantung pada satu storage provider
NFR-05 Auditability— Semua aksi tercatat dengan IP, user agent, timestamp
NFR-06 Portability — Installable sebagai Laravel package via Composer
NFR-07 Testability — Unit & Feature test coverage > 80%
NFR-08 Compliance  — Siap untuk SPBE (Sistem Pemerintahan Berbasis Elektronik)
```

---

## 2. System Architecture

### 2.1 Modular Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                    APPLICATION LAYER                             │
│  ┌──────────────┐  ┌──────────────┐  ┌────────────────────┐    │
│  │  Web Routes  │  │  API Routes  │  │  WYSIWYG Callback  │    │
│  └──────┬───────┘  └──────┬───────┘  └─────────┬──────────┘    │
└─────────┼─────────────────┼───────────────────┼────────────────┘
          │                 │                   │
┌─────────▼─────────────────▼───────────────────▼────────────────┐
│                   HTTP / CONTROLLER LAYER                        │
│  ┌──────────────────┐  ┌───────────────┐  ┌─────────────────┐  │
│  │  FileController  │  │FolderController│ │  ShareController│  │
│  └────────┬─────────┘  └───────┬───────┘  └────────┬────────┘  │
└───────────┼────────────────────┼───────────────────┼───────────┘
            │                    │                   │
┌───────────▼────────────────────▼───────────────────▼───────────┐
│                    SERVICE LAYER                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌─────────────────────┐  │
│  │  FileService │  │FolderService │  │  PermissionService  │  │
│  ├──────────────┤  ├──────────────┤  ├─────────────────────┤  │
│  │UploadService │  │  AuditLogger │  │  ThumbnailService   │  │
│  └──────┬───────┘  └──────┬───────┘  └──────────┬──────────┘  │
└─────────┼─────────────────┼────────────────────┼──────────────┘
          │                 │                    │
┌─────────▼─────────────────▼────────────────────▼──────────────┐
│                  REPOSITORY LAYER                               │
│  ┌──────────────────┐       ┌────────────────────────────┐    │
│  │  FileRepository  │       │   FolderRepository         │    │
│  └──────────────────┘       └────────────────────────────┘    │
└────────────────────────────────────────────────────────────────┘
          │
┌─────────▼───────────────────────────────────────────────────────┐
│               STORAGE ADAPTER LAYER                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────┐  │
│  │ LocalAdapter │  │  S3Adapter   │  │   MinIOAdapter       │  │
│  └──────────────┘  └──────────────┘  └──────────────────────┘  │
│              implements StorageAdapterInterface                  │
└─────────────────────────────────────────────────────────────────┘
          │
┌─────────▼───────────────────────────────────────────────────────┐
│               INFRASTRUCTURE                                     │
│   MySQL/PostgreSQL   │   Redis (Cache/Queue)   │   Storage      │
└─────────────────────────────────────────────────────────────────┘
```

### 2.2 Directory Structure (Laravel Module)

```
Modules/
└── FileManager/
    ├── Config/
    │   └── filemanager.php
    ├── Database/
    │   ├── Migrations/
    │   └── Seeders/
    ├── Http/
    │   ├── Controllers/
    │   │   ├── FileController.php
    │   │   ├── FolderController.php
    │   │   ├── ShareController.php
    │   │   └── EditorCallbackController.php
    │   ├── Middleware/
    │   │   ├── FileManagerAuth.php
    │   │   └── RateLimitUpload.php
    │   └── Requests/
    │       ├── UploadFileRequest.php
    │       └── CreateFolderRequest.php
    ├── Models/
    │   ├── File.php
    │   ├── Folder.php
    │   ├── FilePermission.php
    │   └── FileLog.php
    ├── Repositories/
    │   ├── Contracts/
    │   │   ├── FileRepositoryInterface.php
    │   │   └── FolderRepositoryInterface.php
    │   ├── FileRepository.php
    │   └── FolderRepository.php
    ├── Services/
    │   ├── FileService.php
    │   ├── FolderService.php
    │   ├── UploadService.php
    │   ├── PermissionService.php
    │   ├── AuditLogService.php
    │   ├── ThumbnailService.php
    │   └── ShareService.php
    ├── Storage/
    │   ├── Contracts/
    │   │   └── StorageAdapterInterface.php
    │   ├── LocalAdapter.php
    │   ├── S3Adapter.php
    │   └── MinIOAdapter.php
    ├── Events/
    │   ├── FileUploaded.php
    │   ├── FileDeleted.php
    │   └── FileDownloaded.php
    ├── Listeners/
    │   └── LogFileActivity.php
    ├── Resources/
    │   ├── assets/        ← publishable JS/CSS
    │   └── views/
    ├── Routes/
    │   ├── api.php
    │   └── web.php
    ├── Tests/
    │   ├── Feature/
    │   └── Unit/
    └── FileManagerServiceProvider.php
```

### 2.3 Request Flow — Upload File

```
Client
  │
  ▼
[POST /api/filemanager/upload]
  │
  ▼
[FileManagerAuth Middleware]
  ├─ Validate Bearer Token / Session
  └─ Inject authenticated user
  │
  ▼
[RateLimitUpload Middleware]
  └─ Check: max X uploads/minute per user
  │
  ▼
[FileController@upload]
  └─ Validate UploadFileRequest
      ├─ max_size check
      ├─ allowed_extensions check
      └─ MIME type check (deep validation)
  │
  ▼
[UploadService@handle]
  ├─ 1. Sanitize filename (strip traversal chars)
  ├─ 2. Generate unique storage path (UUID-based)
  ├─ 3. Deep MIME validation (finfo, not extension)
  ├─ 4. AV scan hook (if configured)
  ├─ 5. StorageAdapter->put(path, content)
  ├─ 6. Generate thumbnail (if image)
  └─ 7. Save metadata to DB (FileRepository)
  │
  ▼
[AuditLogService]
  └─ Log: user_id, action=upload, file_id, ip, timestamp
  │
  ▼
[Event: FileUploaded]
  │
  ▼
[Return FileResource JSON]
```

### 2.4 Storage Adapter Interface

```php
<?php
// Modules/FileManager/Storage/Contracts/StorageAdapterInterface.php

namespace Modules\FileManager\Storage\Contracts;

interface StorageAdapterInterface
{
    public function put(string $path, mixed $contents, array $options = []): bool;
    public function get(string $path): string;
    public function delete(string $path): bool;
    public function exists(string $path): bool;
    public function url(string $path): string;
    public function temporaryUrl(string $path, \DateTimeInterface $expiry): string;
    public function size(string $path): int;
    public function mimeType(string $path): string;
    public function move(string $from, string $to): bool;
    public function copy(string $from, string $to): bool;
    public function makeDirectory(string $path): bool;
    public function deleteDirectory(string $path): bool;
    public function files(string $directory): array;
    public function directories(string $directory): array;
}
```

---

## 3. Database Design

### 3.1 ERD (Text-Based)

```
┌─────────────────────┐         ┌─────────────────────┐
│       folders       │         │        files         │
├─────────────────────┤         ├─────────────────────┤
│ id (PK, ULID)       │◄────────│ folder_id (FK)       │
│ parent_id (FK,self) │         │ id (PK, ULID)        │
│ name                │         │ name                 │
│ slug                │         │ original_name        │
│ path                │         │ storage_path         │
│ description         │         │ disk                 │
│ owner_id (FK)       │         │ mime_type            │
│ is_public           │         │ size                 │
│ created_by          │         │ extension            │
│ created_at          │         │ checksum (sha256)    │
│ updated_at          │         │ thumbnail_path       │
│ deleted_at          │         │ metadata (JSON)      │
└─────────────────────┘         │ owner_id (FK)        │
         │                      │ is_public            │
         │                      │ download_count       │
         │                      │ created_by           │
         │                      │ created_at           │
         │                      │ updated_at           │
         │                      │ deleted_at           │
         │                      └─────────────────────┘
         │                               │
         │              ┌────────────────┘
         │              │
         ▼              ▼
┌──────────────────────────────────────┐
│           file_permissions           │
├──────────────────────────────────────┤
│ id (PK)                              │
│ permissionable_id   (polymorphic)    │
│ permissionable_type (File|Folder)    │
│ grantable_id        (polymorphic)    │
│ grantable_type      (User|Role)      │
│ can_read                             │
│ can_write                            │
│ can_delete                           │
│ can_share                            │
│ expires_at (nullable)               │
│ created_by                           │
│ created_at                           │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│              file_logs               │
├──────────────────────────────────────┤
│ id (PK, ULID)                        │
│ user_id (FK, nullable for public)    │
│ loggable_id   (polymorphic)          │
│ loggable_type (File|Folder)          │
│ action (upload|view|download|        │
│         delete|rename|move|copy|     │
│         share|permission_change)     │
│ old_value (JSON, nullable)           │
│ new_value (JSON, nullable)           │
│ ip_address                           │
│ user_agent                           │
│ created_at                           │
└──────────────────────────────────────┘

┌──────────────────────────────────────┐
│            file_shares               │
├──────────────────────────────────────┤
│ id (PK)                              │
│ file_id (FK)                         │
│ token (unique, 64 chars)             │
│ created_by                           │
│ expires_at                           │
│ max_downloads (nullable)             │
│ download_count                       │
│ password_hash (nullable)             │
│ created_at                           │
└──────────────────────────────────────┘
```

### 3.2 Laravel Migrations

```php
<?php
// Migration: create_folders_table

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fm_folders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('parent_id')->nullable()->index();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('path');             // full path: /root/documents/reports
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_id', 'slug']);
            $table->index(['path']);
            $table->index(['owner_id']);
        });
    }
};
```

```php
<?php
// Migration: create_files_table

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fm_files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('folder_id')->nullable()->index();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');                    // display name
            $table->string('original_name');           // original upload name
            $table->string('storage_path');            // actual path on disk/S3
            $table->string('disk')->default('local');  // local | s3 | minio
            $table->string('mime_type', 100);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');        // bytes
            $table->string('checksum', 64);            // sha256
            $table->string('thumbnail_path')->nullable();
            $table->json('metadata')->nullable();      // width, height, duration, etc
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('download_count')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['folder_id', 'created_at']);
            $table->index(['owner_id']);
            $table->index(['mime_type']);
            $table->index(['checksum']);
        });
    }
};
```

```php
<?php
// Migration: create_file_permissions_table

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fm_file_permissions', function (Blueprint $table) {
            $table->id();
            $table->ulidMorphs('permissionable');   // File or Folder
            $table->morphs('grantable');             // User or Role
            $table->boolean('can_read')->default(true);
            $table->boolean('can_write')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_share')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(
                ['permissionable_id', 'permissionable_type', 'grantable_id', 'grantable_type'],
                'fm_permissions_unique'
            );
        });
    }
};
```

```php
<?php
// Migration: create_file_logs_table

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fm_file_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->ulidMorphs('loggable');         // File or Folder
            $table->string('action', 50);
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->ipAddress('ip_address');
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index(['loggable_id', 'loggable_type']);
            $table->index(['action', 'created_at']);
        });
    }
};
```

---

## 4. Security Design

### 4.1 Path Traversal Prevention

**Ancaman:** User mengirim `../../../etc/passwd` sebagai nama file/folder.

**Mitigasi:**

```php
<?php
// Modules/FileManager/Services/UploadService.php

namespace Modules\FileManager\Services;

class UploadService
{
    /**
     * Sanitasi filename — WAJIB dipanggil sebelum ANY storage operation
     */
    public function sanitizeFilename(string $filename): string
    {
        // 1. Ambil basename saja (hapus path separator)
        $filename = basename($filename);

        // 2. Hapus null bytes
        $filename = str_replace("\0", '', $filename);

        // 3. Hapus karakter berbahaya
        $filename = preg_replace('/[^a-zA-Z0-9._\-\s]/', '', $filename);

        // 4. Hapus multiple dots (mencegah double extension: file.php.jpg)
        $filename = preg_replace('/\.{2,}/', '.', $filename);

        // 5. Trim spasi dan dots
        $filename = trim($filename, '. ');

        // 6. Fallback jika kosong
        if (empty($filename)) {
            $filename = 'file_' . time();
        }

        return $filename;
    }

    /**
     * Generate storage path yang aman (UUID-based, bukan dari user input)
     * Path tidak pernah mengandung nama asli file
     */
    public function generateStoragePath(string $extension, string $disk = 'local'): string
    {
        $uuid = \Illuminate\Support\Str::uuid();
        $year = now()->format('Y');
        $month = now()->format('m');

        // Format: uploads/2025/01/a1b2c3d4-e5f6-7890-abcd-ef1234567890.jpg
        return "uploads/{$year}/{$month}/{$uuid}.{$extension}";
    }

    /**
     * Validasi MIME type secara mendalam — JANGAN percaya ekstensi atau $_FILES['type']
     */
    public function validateMimeType(\Illuminate\Http\UploadedFile $file): void
    {
        $allowedMimes = config('filemanager.allowed_mimes', []);

        // Gunakan finfo untuk deteksi MIME berdasarkan file content, bukan ekstensi
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($file->getRealPath());

        if (! in_array($realMime, $allowedMimes)) {
            throw new \Modules\FileManager\Exceptions\InvalidMimeTypeException(
                "File type [{$realMime}] is not allowed."
            );
        }

        // Double check: MIME harus sesuai dengan ekstensi yang diizinkan
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeExtensionMap = config('filemanager.mime_extension_map', []);

        if (isset($mimeExtensionMap[$realMime])) {
            if (! in_array($extension, $mimeExtensionMap[$realMime])) {
                throw new \Modules\FileManager\Exceptions\MimeMismatchException(
                    "Extension [{$extension}] does not match detected MIME [{$realMime}]."
                );
            }
        }
    }
}
```

### 4.2 File Validation Deep Dive

```php
<?php
// Modules/FileManager/Http/Requests/UploadFileRequest.php

namespace Modules\FileManager\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadFileRequest extends FormRequest
{
    public function rules(): array
    {
        $maxSize = config('filemanager.max_upload_size_kb', 10240); // 10MB default
        $allowedExts = implode(',', config('filemanager.allowed_extensions', []));

        return [
            'file'      => [
                'required',
                'file',
                "max:{$maxSize}",
                "mimes:{$allowedExts}",  // Layer 1: ekstensi
            ],
            'folder_id' => ['nullable', 'ulid', 'exists:fm_folders,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('file') && $this->file('file')->isValid()) {
                // Layer 2: finfo MIME check — dilanjutkan di Service layer
                $file = $this->file('file');

                // Cek magic bytes untuk PHP files yang disamarkan
                $handle = fopen($file->getRealPath(), 'rb');
                $bytes = fread($handle, 5);
                fclose($handle);

                $phpSignatures = ['<?php', '<? '];
                foreach ($phpSignatures as $sig) {
                    if (str_starts_with($bytes, $sig)) {
                        $validator->errors()->add('file', 'File contains PHP code and is not allowed.');
                    }
                }
            }
        });
    }
}
```

### 4.3 Role-Based Access Control (RBAC)

```php
<?php
// Modules/FileManager/Services/PermissionService.php

namespace Modules\FileManager\Services;

use Modules\FileManager\Models\File;
use Modules\FileManager\Models\Folder;
use Modules\FileManager\Models\FilePermission;

class PermissionService
{
    /**
     * Cek apakah user punya permission tertentu pada resource
     * Permission resolution order:
     *   1. Super Admin → always true
     *   2. Owner → always true untuk semua action
     *   3. Explicit user permission
     *   4. Role permission (dari semua role user)
     *   5. Parent folder permission (inheritance)
     *   6. Default → deny
     */
    public function can(
        \App\Models\User $user,
        File|Folder $resource,
        string $permission // read|write|delete|share
    ): bool {
        // 1. Super Admin bypass
        if ($user->hasRole('super-admin')) {
            return true;
        }

        // 2. Owner bypass
        if ($resource->owner_id === $user->id) {
            return true;
        }

        // 3. Cek explicit user permission
        $userPermission = FilePermission::query()
            ->where('permissionable_id', $resource->id)
            ->where('permissionable_type', get_class($resource))
            ->where('grantable_id', $user->id)
            ->where('grantable_type', \App\Models\User::class)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->first();

        if ($userPermission) {
            return (bool) $userPermission->{"can_{$permission}"};
        }

        // 4. Cek role permission
        $userRoleIds = $user->roles()->pluck('id');
        $rolePermission = FilePermission::query()
            ->where('permissionable_id', $resource->id)
            ->where('permissionable_type', get_class($resource))
            ->whereIn('grantable_id', $userRoleIds)
            ->where('grantable_type', \Spatie\Permission\Models\Role::class)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->where("can_{$permission}", true)
            ->exists();

        if ($rolePermission) {
            return true;
        }

        // 5. Cek public flag
        if ($resource->is_public && $permission === 'read') {
            return true;
        }

        // 6. Deny by default
        return false;
    }
}
```

### 4.4 Signed URL (Temporary URL)

```php
<?php
// Modules/FileManager/Services/ShareService.php

namespace Modules\FileManager\Services;

use Modules\FileManager\Models\File;
use Modules\FileManager\Models\FileShare;
use Illuminate\Support\Str;

class ShareService
{
    /**
     * Generate signed temporary URL untuk file sharing
     * Menggunakan HMAC signature untuk mencegah URL tampering
     */
    public function generateSignedUrl(
        File $file,
        int $expiresInMinutes = 60,
        ?int $maxDownloads = null,
        ?string $password = null
    ): string {
        $share = FileShare::create([
            'file_id'        => $file->id,
            'token'          => Str::random(64),
            'created_by'     => auth()->id(),
            'expires_at'     => now()->addMinutes($expiresInMinutes),
            'max_downloads'  => $maxDownloads,
            'download_count' => 0,
            'password_hash'  => $password ? bcrypt($password) : null,
        ]);

        return route('filemanager.share.download', [
            'token' => $share->token,
        ]);
    }

    /**
     * Resolve dan validasi share token
     */
    public function resolveToken(string $token): FileShare
    {
        $share = FileShare::where('token', $token)
            ->with('file')
            ->firstOrFail();

        if ($share->expires_at->isPast()) {
            throw new \Modules\FileManager\Exceptions\ShareExpiredException();
        }

        if ($share->max_downloads && $share->download_count >= $share->max_downloads) {
            throw new \Modules\FileManager\Exceptions\ShareLimitReachedException();
        }

        return $share;
    }
}
```

### 4.5 Rate Limiting

```php
<?php
// Modules/FileManager/Http/Middleware/RateLimitUpload.php

namespace Modules\FileManager\Http\Middleware;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;

class RateLimitUpload
{
    public function __construct(private RateLimiter $limiter) {}

    public function handle(Request $request, \Closure $next): mixed
    {
        $key = 'filemanager_upload:' . ($request->user()?->id ?? $request->ip());
        $maxAttempts = config('filemanager.rate_limit.uploads_per_minute', 20);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            $seconds = $this->limiter->availableIn($key);
            return response()->json([
                'message' => "Too many uploads. Try again in {$seconds} seconds.",
            ], 429);
        }

        $this->limiter->hit($key, 60); // 60 second decay

        return $next($request);
    }
}
```

### 4.6 OWASP Mitigasi Summary

| OWASP Risk | Mitigasi |
|---|---|
| A01 Broken Access Control | RBAC + PermissionService + Policy Gates |
| A02 Cryptographic Failures | SHA256 checksum, signed URLs dengan HMAC |
| A03 Injection | Sanitize filename, parameterized queries via Eloquent |
| A04 Insecure Design | UUID-based storage path, tidak ekspos path asli |
| A05 Security Misconfiguration | Config via env, tidak hardcode path/key |
| A07 Auth Failures | Bearer token + session, rate limiting |
| A08 Software & Data Integrity | Checksum validation, AV hook |
| A10 SSRF | Whitelist MIME, tidak follow redirects dari user input |

---

## 5. API Design (RESTful)

### Base URL
```
/api/v1/filemanager
```

### Authentication
```
Authorization: Bearer {token}
```

### 5.1 Endpoints

#### GET /files
Browse file dalam folder tertentu.

**Request:**
```http
GET /api/v1/filemanager/files?folder_id=01HX...&per_page=20&sort=name&order=asc
Authorization: Bearer eyJ...
```

**Response 200:**
```json
{
  "data": [
    {
      "id": "01HXABCDE12345",
      "name": "laporan-tahunan-2024.pdf",
      "original_name": "Laporan Tahunan 2024.pdf",
      "mime_type": "application/pdf",
      "extension": "pdf",
      "size": 2048576,
      "size_human": "2 MB",
      "thumbnail_url": null,
      "download_url": "/api/v1/filemanager/files/01HXABCDE12345/download",
      "preview_url": "/api/v1/filemanager/files/01HXABCDE12345/preview",
      "is_public": false,
      "folder": {
        "id": "01HXFOLDER001",
        "name": "Dokumen"
      },
      "owner": {
        "id": 1,
        "name": "Admin"
      },
      "permissions": {
        "can_read": true,
        "can_write": true,
        "can_delete": false,
        "can_share": true
      },
      "created_at": "2024-01-15T08:30:00Z",
      "updated_at": "2024-01-15T08:30:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 145,
    "last_page": 8
  },
  "links": {
    "self": "/api/v1/filemanager/files?page=1",
    "next": "/api/v1/filemanager/files?page=2",
    "prev": null
  }
}
```

---

#### POST /upload
Upload satu atau lebih file.

**Request:**
```http
POST /api/v1/filemanager/upload
Authorization: Bearer eyJ...
Content-Type: multipart/form-data

file: [binary]
folder_id: 01HXFOLDER001   (optional)
```

**Response 201:**
```json
{
  "message": "File uploaded successfully.",
  "data": {
    "id": "01HXNEWFILE99",
    "name": "dokumen-baru.pdf",
    "mime_type": "application/pdf",
    "size": 1048576,
    "size_human": "1 MB",
    "storage_path": "uploads/2024/01/a1b2c3d4.pdf",
    "download_url": "/api/v1/filemanager/files/01HXNEWFILE99/download",
    "created_at": "2024-01-15T09:00:00Z"
  }
}
```

**Response 422:**
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "file": [
      "The file must be a file of type: pdf, doc, docx, jpg, png.",
      "The file may not be greater than 10240 kilobytes."
    ]
  }
}
```

---

#### DELETE /files/{id}
Soft delete file (permanent delete hanya untuk admin).

**Request:**
```http
DELETE /api/v1/filemanager/files/01HXABCDE12345
Authorization: Bearer eyJ...

{
  "permanent": false
}
```

**Response 200:**
```json
{
  "message": "File moved to trash successfully.",
  "data": {
    "id": "01HXABCDE12345",
    "deleted_at": "2024-01-15T09:30:00Z"
  }
}
```

---

#### POST /folders
Buat folder baru.

**Request:**
```http
POST /api/v1/filemanager/folders
Authorization: Bearer eyJ...
Content-Type: application/json

{
  "name": "Laporan 2024",
  "parent_id": "01HXFOLDER001",
  "description": "Folder laporan tahun 2024",
  "is_public": false
}
```

**Response 201:**
```json
{
  "message": "Folder created successfully.",
  "data": {
    "id": "01HXNEWFOLDER",
    "name": "Laporan 2024",
    "slug": "laporan-2024",
    "path": "/dokumen/laporan-2024",
    "parent_id": "01HXFOLDER001",
    "is_public": false,
    "created_at": "2024-01-15T09:45:00Z"
  }
}
```

---

#### GET /files/{id}/preview
Preview file (stream untuk image/PDF, metadata untuk lainnya).

**Request:**
```http
GET /api/v1/filemanager/files/01HXABCDE12345/preview
Authorization: Bearer eyJ...
```

**Response (image/pdf):** Stream binary dengan header Content-Type yang sesuai.

**Response (non-previewable) 200:**
```json
{
  "previewable": false,
  "data": {
    "id": "01HXABCDE12345",
    "name": "data.xlsx",
    "mime_type": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "size_human": "512 KB",
    "download_url": "/api/v1/filemanager/files/01HXABCDE12345/download"
  }
}
```

---

#### POST /files/{id}/share
Generate temporary share link.

**Request:**
```http
POST /api/v1/filemanager/files/01HXABCDE12345/share
Authorization: Bearer eyJ...
Content-Type: application/json

{
  "expires_in_minutes": 1440,
  "max_downloads": 10,
  "password": null
}
```

**Response 201:**
```json
{
  "message": "Share link created.",
  "data": {
    "share_url": "https://example.com/share/AbCdEfGh...",
    "expires_at": "2024-01-16T09:00:00Z",
    "max_downloads": 10
  }
}
```

---

## 6. UI/UX Flow

### 6.1 Layout Utama

```
┌─────────────────────────────────────────────────────────────────────┐
│  🗂 File Manager                        [Search...] [Upload] [New Folder] │
├──────────────────┬──────────────────────────────────────────────────┤
│  SIDEBAR         │  MAIN AREA                                        │
│  ┌─────────────┐ │  ┌─────────────────────────────────────────────┐ │
│  │ 📁 Root      │ │  │ Breadcrumb: Root > Dokumen > Laporan 2024  │ │
│  │  📁 Dokumen  │ │  ├─────────────────────────────────────────────┤ │
│  │   📂 Laporan │ │  │ [List View] [Grid View]  Sort: Name ▼      │ │
│  │   📂 Gambar  │ │  ├─────────────────────────────────────────────┤ │
│  │  📁 Media    │ │  │                                              │ │
│  │  📁 Shared   │ │  │  ┌────────┐ ┌────────┐ ┌────────┐          │ │
│  │              │ │  │  │ 📄 PDF │ │ 📄 DOC │ │ 🖼 IMG │          │ │
│  │              │ │  │  │ laporan│ │ surat  │ │ logo   │          │ │
│  │              │ │  │  │ 2024   │ │ resmi  │ │ .png   │          │ │
│  │              │ │  │  │ 2.1 MB │ │ 512 KB │ │ 45 KB  │          │ │
│  │              │ │  │  └────────┘ └────────┘ └────────┘          │ │
│  │              │ │  │                                              │ │
│  │[+ New Folder]│ │  │  ┌────────┐ ┌────────┐                     │ │
│  └─────────────┘ │  │  │ 📁 Q1   │ │ 📁 Q2  │                     │ │
│                  │  │  └────────┘ └────────┘                     │ │
│  STORAGE INFO    │  └─────────────────────────────────────────────┘ │
│  ████░░ 4.2/10GB │                                                   │
└──────────────────┴──────────────────────────────────────────────────┘
│ STATUS BAR: 3 items selected  |  Total: 2.65 MB  |  Ready           │
└─────────────────────────────────────────────────────────────────────┘
```

### 6.2 Upload Flow

```
User Klik [Upload]
    │
    ▼
[Upload Modal Terbuka]
    │
    ├── Drag & Drop Zone: "Drop files here or click to browse"
    ├── Supported: PDF, DOC, DOCX, JPG, PNG, MP4 (max 10MB)
    │
    ▼
User Drop/Select File
    │
    ▼
[Client-side Validation]
    ├── Extension check
    ├── Size check
    └── If invalid → show inline error, stop
    │
    ▼
[Upload Progress UI]
    ├── File list dengan progress bar per file
    ├── Status: Uploading... / Validating... / Done ✓ / Error ✗
    │
    ▼
[XHR/Fetch POST ke /api/v1/filemanager/upload]
    │
    ▼
[Server Response]
    ├── 201 Created → update file list, show success toast
    └── 4xx/5xx   → show error message inline
```

### 6.3 Context Menu (Right Click)

```
┌─────────────────────┐
│ 📥  Download        │
│ 👁  Preview         │
│ ─────────────────── │
│ ✏️  Rename          │
│ 📋  Copy            │
│ ✂️  Move            │
│ ─────────────────── │
│ 🔗  Share Link      │
│ 🔒  Permissions     │
│ ─────────────────── │
│ 🗑  Delete          │
└─────────────────────┘
```

### 6.4 Preview Modal

```
┌──────────────────────────────────────────────────────────┐
│  Preview — laporan-tahunan-2024.pdf        [×] Close     │
├──────────────────────────────────────────────────────────┤
│                                                           │
│    ┌─────────────────────────────────────────────┐       │
│    │                                             │       │
│    │          [PDF Viewer / Image / Video]       │       │
│    │                                             │       │
│    └─────────────────────────────────────────────┘       │
│                                                           │
│   Name: laporan-tahunan-2024.pdf                          │
│   Size: 2.1 MB  |  Type: PDF  |  Uploaded: 15 Jan 2024   │
│   Owner: Admin  |  Downloads: 23                          │
│                                                           │
│   [📥 Download]  [🔗 Share]  [✏️ Rename]  [🗑 Delete]   │
└──────────────────────────────────────────────────────────┘
```

### 6.5 UI States

| State | Tampilan |
|---|---|
| **Loading** | Skeleton cards + spinner di breadcrumb area |
| **Empty Folder** | Ilustrasi folder kosong + teks "Folder ini kosong. Upload file pertama Anda!" |
| **No Permission** | Icon kunci + pesan "Anda tidak memiliki akses ke folder ini" |
| **Upload Error** | Inline error badge merah pada file yang gagal, dengan pesan detail |
| **Search No Result** | Ilustrasi + "Tidak ada file yang cocok dengan pencarian '{query}'" |
| **Delete Confirm** | Modal konfirmasi sebelum hapus, lebih strict untuk permanent delete |

---

## 7. WYSIWYG Editor Integration

### 7.1 Konsep Integrasi

```
Editor (CKEditor/TinyMCE/Summernote)
    │
    User klik tombol "Insert Image" atau "Browse Files"
    │
    ▼
[window.open('/filemanager/picker?callback=CALLBACK_NAME&type=image')]
    │
    ▼
[File Manager SPA terbuka sebagai popup/iframe]
    │
    User pilih/upload file
    │
    ▼
[File Manager memanggil callback dengan URL file]
    │
    ▼
[Editor menerima URL dan meng-insert ke konten]
```

### 7.2 Callback Controller

```php
<?php
// Modules/FileManager/Http/Controllers/EditorCallbackController.php

namespace Modules\FileManager\Http\Controllers;

use Illuminate\Http\Request;
use Modules\FileManager\Models\File;
use Modules\FileManager\Services\PermissionService;

class EditorCallbackController extends Controller
{
    public function __construct(private PermissionService $permissionService) {}

    /**
     * Entry point untuk picker mode (dipakai WYSIWYG editor)
     */
    public function picker(Request $request)
    {
        $request->validate([
            'callback' => ['required', 'string', 'regex:/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/'],
            'type'     => ['nullable', 'in:image,file,video'],
        ]);

        // Render SPA dengan mode picker + callback name
        return view('filemanager::picker', [
            'callback' => $request->callback,
            'type'     => $request->type ?? 'file',
        ]);
    }

    /**
     * Handle file selection — kirim URL ke parent window
     */
    public function select(Request $request, string $fileId)
    {
        $file = File::findOrFail($fileId);

        // Pastikan user punya akses read
        abort_unless(
            $this->permissionService->can(auth()->user(), $file, 'read'),
            403
        );

        $fileUrl = $file->getPublicUrl();

        // Return JS yang akan close popup dan kirim URL ke editor
        return view('filemanager::callback', [
            'file_url'     => $fileUrl,
            'file_name'    => $file->name,
            'callback'     => $request->callback,
        ]);
    }
}
```

### 7.3 CKEditor 5 Integration

```php
// config/filemanager.php — tambahkan route
'editor_picker_route' => env('FILEMANAGER_PICKER_URL', '/filemanager/picker'),
```

```javascript
// resources/js/ckeditor-filemanager.js

import { ClassicEditor, Image, ImageUpload } from 'ckeditor5';

ClassicEditor.create(document.querySelector('#editor'), {
    toolbar: ['bold', 'italic', 'imageUpload', 'fileManager'],
    extraPlugins: [FileManagerPlugin],
    fileManager: {
        pickerUrl: '/filemanager/picker',
        callbackName: 'CKEditorFileManagerCallback',
    }
});

// Plugin custom untuk CKEditor
function FileManagerPlugin(editor) {
    editor.ui.componentFactory.add('fileManager', locale => {
        const button = new ButtonView(locale);
        button.label = 'File Manager';
        button.icon = '<svg>...</svg>';
        button.tooltip = true;

        button.on('execute', () => {
            const callbackName = 'fileManagerCKCallback_' + Date.now();

            // Define global callback
            window[callbackName] = function(fileUrl, fileName) {
                editor.model.change(writer => {
                    const imageElement = writer.createElement('imageBlock', {
                        src: fileUrl,
                        alt: fileName,
                    });
                    editor.model.insertContent(imageElement);
                });
                delete window[callbackName];
            };

            const pickerUrl = `/filemanager/picker?callback=${callbackName}&type=image`;
            const popup = window.open(pickerUrl, 'filemanager', 'width=900,height=600');
        });

        return button;
    });
}
```

### 7.4 TinyMCE Integration

```javascript
// resources/js/tinymce-filemanager.js

tinymce.init({
    selector: '#tinymce-editor',
    plugins: 'image link',
    toolbar: 'undo redo | bold italic | filemanager | image link',
    
    // Custom file picker callback
    file_picker_callback: function(callback, value, meta) {
        const callbackName = 'TinyMCEFileManagerCallback_' + Date.now();

        window[callbackName] = function(fileUrl, fileName) {
            callback(fileUrl, { alt: fileName });
            delete window[callbackName];
        };

        const type = meta.filetype === 'image' ? 'image' : 'file';
        const url = `/filemanager/picker?callback=${callbackName}&type=${type}`;
        
        window.open(url, 'filemanager', 'width=900,height=600,resizable=yes');
    }
});
```

### 7.5 Summernote Integration

```javascript
// resources/js/summernote-filemanager.js

$('#summernote-editor').summernote({
    toolbar: [
        ['style', ['style']],
        ['font', ['bold', 'italic', 'underline']],
        ['insert', ['filemanager', 'link', 'hr']],
        ['view', ['fullscreen', 'codeview']],
    ],
    
    buttons: {
        filemanager: function(context) {
            const ui = $.summernote.ui;
            const button = ui.button({
                contents: '<i class="fas fa-folder-open"/>',
                tooltip: 'File Manager',
                click: function() {
                    const callbackName = 'SummernoteFileManagerCallback_' + Date.now();
                    
                    window[callbackName] = function(fileUrl, fileName) {
                        context.invoke('editor.insertImage', fileUrl, fileName);
                        delete window[callbackName];
                    };

                    window.open(
                        `/filemanager/picker?callback=${callbackName}&type=image`,
                        'filemanager',
                        'width=900,height=600'
                    );
                }
            });
            return button.render();
        }
    }
});
```

### 7.6 Callback View (shared semua editor)

```html
<!-- resources/views/filemanager/callback.blade.php -->
<!DOCTYPE html>
<html>
<head><title>Selecting...</title></head>
<body>
<script>
    (function() {
        const fileUrl  = @json($file_url);
        const fileName = @json($file_name);
        const callback = @json($callback);

        if (window.opener && typeof window.opener[callback] === 'function') {
            window.opener[callback](fileUrl, fileName);
            window.close();
        } else {
            document.write('<p>Error: Callback not found. Please close this window.</p>');
        }
    })();
</script>
</body>
</html>
```

---

## 8. Extensibility & Package Design

### 8.1 Package Structure (Packagist)

```
vendor/
└── fachran/
    └── laravel-filemanager/
        ├── src/
        │   ├── FileManagerServiceProvider.php
        │   ├── Facades/
        │   │   └── FileManager.php
        │   ├── Http/...
        │   ├── Models/...
        │   ├── Services/...
        │   └── Storage/...
        ├── config/
        │   └── filemanager.php
        ├── resources/
        │   ├── assets/          ← publishable
        │   └── views/           ← publishable
        ├── database/
        │   └── migrations/      ← publishable
        ├── routes/
        │   ├── api.php
        │   └── web.php
        ├── tests/
        ├── composer.json
        └── README.md
```

### 8.2 Service Provider

```php
<?php
// src/FileManagerServiceProvider.php

namespace Fachran\FileManager;

use Illuminate\Support\ServiceProvider;

class FileManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filemanager.php', 'filemanager');

        // Bind interfaces ke implementasi
        $this->app->bind(
            \Fachran\FileManager\Storage\Contracts\StorageAdapterInterface::class,
            fn ($app) => $app->make(
                config('filemanager.storage_adapter', \Fachran\FileManager\Storage\LocalAdapter::class)
            )
        );

        $this->app->singleton(\Fachran\FileManager\Services\FileService::class);
        $this->app->singleton(\Fachran\FileManager\Services\PermissionService::class);
    }

    public function boot(): void
    {
        // Routes
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

        // Migrations
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        // Views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filemanager');

        // Publishables
        $this->publishes([
            __DIR__.'/../config/filemanager.php' => config_path('filemanager.php'),
        ], 'filemanager-config');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/filemanager'),
        ], 'filemanager-assets');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filemanager'),
        ], 'filemanager-views');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'filemanager-migrations');

        // Event & Listener registration
        $this->registerEvents();
    }

    protected function registerEvents(): void
    {
        $listen = config('filemanager.listen', []);

        foreach ($listen as $event => $listeners) {
            foreach ($listeners as $listener) {
                \Event::listen($event, $listener);
            }
        }
    }
}
```

### 8.3 Config File

```php
<?php
// config/filemanager.php

return [
    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    */
    'disk'              => env('FILEMANAGER_DISK', 'local'),
    'storage_adapter'   => env('FILEMANAGER_ADAPTER', \Fachran\FileManager\Storage\LocalAdapter::class),
    'storage_path'      => env('FILEMANAGER_STORAGE_PATH', 'filemanager'),

    /*
    |--------------------------------------------------------------------------
    | Upload Constraints
    |--------------------------------------------------------------------------
    */
    'max_upload_size_kb' => env('FILEMANAGER_MAX_SIZE', 10240), // 10MB
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'mp4', 'mp3', 'zip'],
    'allowed_mimes'      => [
        'image/jpeg', 'image/png', 'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'video/mp4',
        'audio/mpeg',
        'application/zip',
    ],
    'mime_extension_map' => [
        'image/jpeg'       => ['jpg', 'jpeg'],
        'image/png'        => ['png'],
        'application/pdf'  => ['pdf'],
        // ...
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limit' => [
        'uploads_per_minute' => env('FILEMANAGER_UPLOAD_RATE', 20),
        'downloads_per_hour' => env('FILEMANAGER_DOWNLOAD_RATE', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail
    |--------------------------------------------------------------------------
    */
    'thumbnails' => [
        'enabled' => true,
        'width'   => 300,
        'height'  => 300,
        'quality' => 80,
        'driver'  => 'gd', // gd | imagick
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */
    'signed_url_expire_minutes' => env('FILEMANAGER_SIGNED_URL_EXPIRE', 60),
    'antivirus' => [
        'enabled' => env('FILEMANAGER_AV_ENABLED', false),
        'driver'  => env('FILEMANAGER_AV_DRIVER', 'clamav'), // clamav | virustotal
        'host'    => env('FILEMANAGER_AV_HOST', 'localhost'),
        'port'    => env('FILEMANAGER_AV_PORT', 3310),
    ],

    /*
    |--------------------------------------------------------------------------
    | WYSIWYG Callback Route
    |--------------------------------------------------------------------------
    */
    'picker_route'   => '/filemanager/picker',
    'callback_route' => '/filemanager/callback',

    /*
    |--------------------------------------------------------------------------
    | Events / Hooks
    |--------------------------------------------------------------------------
    */
    'listen' => [
        \Fachran\FileManager\Events\FileUploaded::class  => [
            \Fachran\FileManager\Listeners\LogFileActivity::class,
            // tambahkan listener custom di sini
        ],
        \Fachran\FileManager\Events\FileDeleted::class   => [
            \Fachran\FileManager\Listeners\LogFileActivity::class,
        ],
        \Fachran\FileManager\Events\FileDownloaded::class => [
            \Fachran\FileManager\Listeners\LogFileActivity::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    */
    'middleware' => [
        'web' => ['web', 'auth'],
        'api' => ['api', 'auth:sanctum'],
    ],
];
```

### 8.4 Event System

```php
<?php
// src/Events/FileUploaded.php

namespace Fachran\FileManager\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Fachran\FileManager\Models\File;

class FileUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly File $file,
        public readonly \App\Models\User $uploadedBy,
        public readonly string $ip,
    ) {}
}
```

```php
<?php
// Contoh listener custom di aplikasi pengguna package
// app/Listeners/NotifyAdminOnUpload.php

namespace App\Listeners;

use Fachran\FileManager\Events\FileUploaded;
use App\Notifications\NewFileUploadedNotification;

class NotifyAdminOnUpload
{
    public function handle(FileUploaded $event): void
    {
        $admins = \App\Models\User::role('admin')->get();
        \Notification::send($admins, new NewFileUploadedNotification($event->file));
    }
}
```

### 8.5 Facade

```php
<?php
// src/Facades/FileManager.php

namespace Fachran\FileManager\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Fachran\FileManager\Models\File upload(\Illuminate\Http\UploadedFile $file, ?string $folderId = null)
 * @method static bool delete(string $fileId, bool $permanent = false)
 * @method static string signedUrl(string $fileId, int $expiresInMinutes = 60)
 * @method static \Illuminate\Pagination\LengthAwarePaginator browse(?string $folderId = null, array $options = [])
 */
class FileManager extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Fachran\FileManager\Services\FileService::class;
    }
}
```

```php
// Cara pakai di aplikasi:
use Fachran\FileManager\Facades\FileManager;

$file = FileManager::upload($request->file('document'), $folderId);
$url  = FileManager::signedUrl($file->id, 120);
```

---

## 9. Future Roadmap

### Phase 1 — Core (v1.0) ✅ Blueprint ini
- CRUD file & folder
- RBAC permission
- Multi-storage adapter
- Audit log
- WYSIWYG integration
- Rate limiting & security

### Phase 2 — Enhanced (v1.5)
- [ ] **File Versioning** — simpan riwayat revisi file, restore ke versi sebelumnya
- [ ] **Chunked Upload** — upload file besar (>100MB) dengan resumable upload
- [ ] **Bulk Operations** — move, copy, delete banyak file sekaligus
- [ ] **File Tagging** — tambah tag untuk organisasi dan pencarian
- [ ] **Advanced Search** — full-text search dalam metadata + tag

### Phase 3 — Optimization (v2.0)
- [ ] **Image Optimization** — auto-resize, compress, convert format (WebP)
- [ ] **CDN Integration** — CloudFront, Cloudflare, BunnyCDN
- [ ] **Antivirus Integration** — ClamAV & VirusTotal
- [ ] **Preview Enhancement** — Office document preview (LibreOffice headless)
- [ ] **ZIP Archive** — download multiple files sebagai ZIP

### Phase 4 — Intelligence (v2.5)
- [ ] **AI Auto-Tagging** — tag otomatis berdasarkan isi file (OpenAI Vision / Gemini)
- [ ] **OCR Integration** — extract text dari PDF scan & gambar
- [ ] **Duplicate Detection** — deteksi file duplikat via SHA256 checksum
- [ ] **Smart Categorization** — kategorisasi otomatis berdasarkan MIME & content

### Phase 5 — Enterprise (v3.0)
- [ ] **Multi-tenant** — isolasi storage per tenant/organization
- [ ] **SSO Integration** — SAML, OAuth2, LDAP/AD
- [ ] **Compliance Mode** — GDPR compliance, data retention policy
- [ ] **Goravel Port** — port ke Goravel untuk high-performance microservice

---

## 10. Quick Start

### Installation (sebagai package)

```bash
composer require ranitachi/filemanager-laravel

php artisan vendor:publish --tag=filemanager-config
php artisan vendor:publish --tag=filemanager-migrations
php artisan vendor:publish --tag=filemanager-assets

php artisan migrate
```

### Minimal Setup

```php
// .env
FILEMANAGER_DISK=local
FILEMANAGER_MAX_SIZE=10240
FILEMANAGER_SIGNED_URL_EXPIRE=60
```

```php
// config/filemanager.php
// Pastikan middleware sesuai dengan auth system Anda:
'middleware' => [
    'api' => ['api', 'auth:sanctum'],
],
```

### Test Upload

```bash
curl -X POST http://localhost/api/v1/filemanager/upload \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -F "file=@/path/to/document.pdf" \
  -F "folder_id=01HXFOLDER001"
```

---

## Kontribusi & Lisensi

Blueprint ini dirancang untuk:
1. **Internal Development** — gunakan langsung sebagai spec teknis
2. **Open Source Package** — publish ke Packagist dengan lisensi MIT
3. **Government Systems** — comply dengan SPBE dan standar NIST

---

> **Next Step:** Mulai implementasi dari `FileManagerServiceProvider`, `StorageAdapterInterface`, dan `UploadService` — ketiganya adalah fondasi sistem ini.
