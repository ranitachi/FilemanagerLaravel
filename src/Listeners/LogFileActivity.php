<?php

namespace Fachran\FileManager\Listeners;

use Fachran\FileManager\Events\FileDeleted;
use Fachran\FileManager\Events\FileDownloaded;
use Fachran\FileManager\Events\FileMoved;
use Fachran\FileManager\Events\FileUploaded;
use Fachran\FileManager\Events\FolderCreated;
use Fachran\FileManager\Models\FileLog;

class LogFileActivity
{
    public function handle(FileUploaded|FileDeleted|FileDownloaded|FileMoved|FolderCreated $event): void
    {
        match (true) {
            $event instanceof FileUploaded   => $this->logFileEvent($event->file, FileLog::ACTION_UPLOAD, $event->uploadedBy, $event->ip),
            $event instanceof FileDeleted    => $this->logFileEvent($event->file, FileLog::ACTION_DELETE, $event->deletedBy),
            $event instanceof FileDownloaded => $this->logFileEvent($event->file, FileLog::ACTION_DOWNLOAD, $event->downloadedBy, $event->ip),
            $event instanceof FileMoved      => $this->logFileEvent($event->file, FileLog::ACTION_MOVE, $event->movedBy, null, ['folder_id' => $event->fromFolderId], ['folder_id' => $event->toFolderId]),
            $event instanceof FolderCreated  => $this->logFolderEvent($event->folder, FileLog::ACTION_FOLDER_CREATE, $event->createdBy),
            default => null,
        };
    }

    protected function logFileEvent($resource, string $action, $user = null, ?string $ip = null, array $old = [], array $new = []): void
    {
        FileLog::create([
            'user_id'       => $user?->getKey(),
            'loggable_id'   => $resource->getKey(),
            'loggable_type' => get_class($resource),
            'action'        => $action,
            'old_value'     => empty($old) ? null : $old,
            'new_value'     => empty($new) ? null : $new,
            'ip_address'    => $ip ?? request()->ip() ?? '0.0.0.0',
            'user_agent'    => request()->userAgent(),
        ]);
    }

    protected function logFolderEvent($resource, string $action, $user = null): void
    {
        $this->logFileEvent($resource, $action, $user);
    }
}
