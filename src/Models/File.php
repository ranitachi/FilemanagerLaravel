<?php

namespace Fachran\FileManager\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class File extends Model
{
    use HasUlids, SoftDeletes, HasFactory;

    protected static function newFactory()
    {
        return \Fachran\FileManager\Database\Factories\FileFactory::new();
    }

    protected $table = 'fm_files';

    protected $fillable = [
        'folder_id',
        'owner_id',
        'name',
        'original_name',
        'storage_path',
        'disk',
        'mime_type',
        'extension',
        'size',
        'checksum',
        'thumbnail_path',
        'metadata',
        'is_public',
        'download_count',
        'created_by',
    ];

    protected $casts = [
        'metadata'       => 'array',
        'is_public'      => 'boolean',
        'size'           => 'integer',
        'download_count' => 'integer',
    ];

    protected $appends = ['size_human', 'url', 'thumbnail_url'];

    // ── Relations ─────────────────────────────────────────────────────────────

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'owner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function permissions(): MorphMany
    {
        return $this->morphMany(FilePermission::class, 'permissionable');
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(FileLog::class, 'loggable');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(FileShare::class);
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getSizeHumanAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;

        while ($bytes >= 1024 && $i < 4) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getUrlAttribute(): string
    {
        return route('filemanager.files.download', $this->id);
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        return Storage::disk($this->disk)->url($this->thumbnail_path);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isAudio(): bool
    {
        return str_starts_with($this->mime_type, 'audio/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function isPreviewable(): bool
    {
        return $this->isImage() || $this->isPdf() || $this->isVideo() || $this->isAudio();
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }

    public function getStorageDisk(): string
    {
        return $this->disk ?? config('filemanager.disk', 'local');
    }
}
