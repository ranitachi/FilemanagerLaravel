<?php

namespace Fachran\FileManager\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FileLog extends Model
{
    use HasUlids;

    protected $table = 'fm_file_logs';

    public const UPDATED_AT = null; // Only created_at

    public const ACTION_UPLOAD     = 'upload';
    public const ACTION_DOWNLOAD   = 'download';
    public const ACTION_VIEW       = 'view';
    public const ACTION_DELETE     = 'delete';
    public const ACTION_RESTORE    = 'restore';
    public const ACTION_RENAME     = 'rename';
    public const ACTION_MOVE       = 'move';
    public const ACTION_COPY       = 'copy';
    public const ACTION_SHARE      = 'share';
    public const ACTION_PERMISSION = 'permission_change';
    public const ACTION_FOLDER_CREATE = 'folder_create';
    public const ACTION_FOLDER_DELETE = 'folder_delete';

    protected $fillable = [
        'user_id',
        'loggable_id',
        'loggable_type',
        'action',
        'old_value',
        'new_value',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function loggable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
}
