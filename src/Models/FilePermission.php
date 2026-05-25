<?php

namespace Fachran\FileManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FilePermission extends Model
{
    protected $table = 'fm_file_permissions';

    protected $fillable = [
        'permissionable_id',
        'permissionable_type',
        'grantable_id',
        'grantable_type',
        'can_read',
        'can_write',
        'can_delete',
        'can_share',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'can_read'   => 'boolean',
        'can_write'  => 'boolean',
        'can_delete' => 'boolean',
        'can_share'  => 'boolean',
        'expires_at' => 'datetime',
    ];

    public function permissionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function grantable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
        });
    }
}
