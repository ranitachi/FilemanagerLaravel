# Changelog

All notable changes to this project will be documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.0.0/).

## [Unreleased]

## [1.0.0] — 2025-01-01

### Added
- Initial release
- Secure file upload with deep MIME validation and magic byte checking
- Multi-storage adapter: Local, S3, MinIO (interface-based, fully swappable)
- Role-Based Access Control (RBAC) — per-file and per-folder permissions
- Audit log for all file operations
- Folder tree with infinite nesting
- Signed URL / share links with expiry, download limits, and optional password
- WYSIWYG editor picker: CKEditor 5, TinyMCE, Summernote
- Thumbnail generation via Intervention Image v3
- Rate limiting on upload endpoints
- Optional ClamAV antivirus hook
- REST API with JSON:API-style responses
- Event/Hook system: FileUploaded, FileDeleted, FileDownloaded, FileMoved, FolderCreated
- Laravel Facade: `FileManager::upload()`, `FileManager::download()`, etc.
- Soft delete + permanent delete (admin only)
- Full test suite via PestPHP
