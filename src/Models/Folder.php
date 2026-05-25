<?php

namespace Fachran\FileManager\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Folder extends Model
{
    use HasUlids, SoftDeletes;

    protected $table = 'fm_folders';

    protected $fillable = [
        'parent_id',
        'owner_id',
        'name',
        'slug',
        'path',
        'description',
        'is_public',
        'created_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    // ── Lifecycle ─────────────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Folder $folder) {
            if (empty($folder->slug)) {
                $folder->slug = Str::slug($folder->name);
            }

            if (empty($folder->path)) {
                $parent = $folder->parent_id
                    ? static::find($folder->parent_id)
                    : null;

                $folder->path = $parent
                    ? rtrim($parent->path, '/').'/'.$folder->slug
                    : '/'.$folder->slug;
            }
        });
    }

    // ── Relations ─────────────────────────────────────────────────────────────

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'folder_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'owner_id');
    }

    public function permissions(): MorphMany
    {
        return $this->morphMany(FilePermission::class, 'permissionable');
    }

    public function logs(): MorphMany
    {
        return $this->morphMany(FileLog::class, 'loggable');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isRoot(): bool
    {
        return $this->parent_id === null;
    }

    public function getDepth(): int
    {
        return substr_count(trim($this->path, '/'), '/');
    }

    /**
     * Ancestors ordered from root to current
     */
    public function getAncestors(): \Illuminate\Support\Collection
    {
        $ancestors = collect();
        $path = '';

        foreach (explode('/', trim($this->path, '/')) as $slug) {
            $path .= '/'.$slug;
            $folder = static::where('path', $path)->first();
            if ($folder && $folder->id !== $this->id) {
                $ancestors->push($folder);
            }
        }

        return $ancestors;
    }
}
