<?php

namespace Fachran\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileShare extends Model
{
    protected $table = 'fm_file_shares';

    protected $fillable = [
        'file_id',
        'token',
        'created_by',
        'expires_at',
        'max_downloads',
        'download_count',
        'password_hash',
    ];

    protected $casts = [
        'expires_at'     => 'datetime',
        'max_downloads'  => 'integer',
        'download_count' => 'integer',
    ];

    protected $hidden = ['password_hash'];

    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isLimitReached(): bool
    {
        return $this->max_downloads !== null
            && $this->download_count >= $this->max_downloads;
    }

    public function isValid(): bool
    {
        return ! $this->isExpired() && ! $this->isLimitReached();
    }

    public function hasPassword(): bool
    {
        return ! empty($this->password_hash);
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password_hash);
    }

    public function getShareUrl(): string
    {
        return route('filemanager.share.show', $this->token);
    }

    public function incrementDownloadCount(): void
    {
        $this->increment('download_count');
    }
}
