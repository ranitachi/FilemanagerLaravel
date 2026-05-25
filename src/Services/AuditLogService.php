<?php

namespace Fachran\FileManager\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Fachran\FileManager\Models\File;
use Fachran\FileManager\Models\FileLog;
use Fachran\FileManager\Models\Folder;

class AuditLogService
{
    public function log(
        File|Folder $resource,
        string $action,
        ?Authenticatable $user = null,
        array $oldValue = [],
        array $newValue = [],
    ): FileLog {
        return FileLog::create([
            'user_id'      => $user?->getKey(),
            'loggable_id'  => $resource->getKey(),
            'loggable_type'=> get_class($resource),
            'action'       => $action,
            'old_value'    => empty($oldValue) ? null : $oldValue,
            'new_value'    => empty($newValue) ? null : $newValue,
            'ip_address'   => request()->ip() ?? '0.0.0.0',
            'user_agent'   => request()->userAgent(),
        ]);
    }
}
