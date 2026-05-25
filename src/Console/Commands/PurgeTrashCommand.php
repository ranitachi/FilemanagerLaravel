<?php

namespace Fachran\FileManager\Console\Commands;

use Illuminate\Console\Command;
use Fachran\FileManager\Models\File;
use Fachran\FileManager\Models\Folder;
use Fachran\FileManager\Storage\Contracts\StorageAdapterInterface;

class PurgeTrashCommand extends Command
{
    protected $signature = 'filemanager:purge-trash
                            {--days= : Override the auto_purge_days from config}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Permanently delete files that have been in trash longer than the configured retention period';

    public function __construct(protected StorageAdapterInterface $storage)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?? config('filemanager.trash.auto_purge_days', 30));
        $dryRun = $this->option('dry-run');
        $cutoff = now()->subDays($days);

        $files = File::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->get();

        $folders = Folder::onlyTrashed()
            ->where('deleted_at', '<=', $cutoff)
            ->get();

        if ($files->isEmpty() && $folders->isEmpty()) {
            $this->info('Trash is clean — nothing to purge.');
            return self::SUCCESS;
        }

        $this->table(
            ['Type', 'Name', 'Deleted At'],
            $files->map(fn ($f)  => ['File',   $f->name, $f->deleted_at->toDateTimeString()])
                ->concat($folders->map(fn ($f) => ['Folder', $f->name, $f->deleted_at->toDateTimeString()]))
        );

        $this->line('');
        $this->line("Items to purge: <comment>{$files->count()} files, {$folders->count()} folders</comment>");

        if ($dryRun) {
            $this->warn('[DRY RUN] No files were deleted.');
            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Permanently delete these items?')) {
            $this->info('Aborted.');
            return self::SUCCESS;
        }

        $purgedFiles   = 0;
        $purgedFolders = 0;

        foreach ($files as $file) {
            try {
                // Delete physical file
                if ($this->storage->exists($file->storage_path)) {
                    $this->storage->delete($file->storage_path);
                }
                if ($file->thumbnail_path && $this->storage->exists($file->thumbnail_path)) {
                    $this->storage->delete($file->thumbnail_path);
                }
                $file->forceDelete();
                $purgedFiles++;
            } catch (\Throwable $e) {
                $this->error("Failed to purge file [{$file->id}]: {$e->getMessage()}");
            }
        }

        foreach ($folders as $folder) {
            try {
                $folder->forceDelete();
                $purgedFolders++;
            } catch (\Throwable $e) {
                $this->error("Failed to purge folder [{$folder->id}]: {$e->getMessage()}");
            }
        }

        $this->info("✅ Purged {$purgedFiles} file(s) and {$purgedFolders} folder(s).");
        return self::SUCCESS;
    }
}
