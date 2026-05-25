<?php

namespace Fachran\FileManager\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature   = 'filemanager:install';
    protected $description = 'Install and set up Laravel File Manager package';

    public function handle(): int
    {
        $this->info('');
        $this->info('  ╔═══════════════════════════════════════╗');
        $this->info('  ║   Laravel Secure File Manager Setup   ║');
        $this->info('  ╚═══════════════════════════════════════╝');
        $this->info('');

        // 1. Publish config
        $this->callSilent('vendor:publish', ['--tag' => 'filemanager-config', '--force' => false]);
        $this->line('  ✅ Config published → <comment>config/filemanager.php</comment>');

        // 2. Publish migrations
        $this->callSilent('vendor:publish', ['--tag' => 'filemanager-migrations', '--force' => false]);
        $this->line('  ✅ Migrations published → <comment>database/migrations/</comment>');

        // 3. Publish assets
        $this->callSilent('vendor:publish', ['--tag' => 'filemanager-assets', '--force' => false]);
        $this->line('  ✅ Assets published → <comment>public/vendor/filemanager/</comment>');

        // 4. Run migrations
        if ($this->confirm('  Run database migrations now?', true)) {
            $this->call('migrate');
            $this->line('  ✅ Migrations executed.');
        }

        // 5. Storage link
        if ($this->confirm('  Create storage symlink (php artisan storage:link)?', true)) {
            $this->callSilent('storage:link');
            $this->line('  ✅ Storage link created.');
        }

        $this->info('');
        $this->info('  🎉 File Manager installed successfully!');
        $this->info('');
        $this->line('  Next steps:');
        $this->line('  1. Set FILEMANAGER_DISK in your <comment>.env</comment> (local|s3|minio)');
        $this->line('  2. Configure middleware in <comment>config/filemanager.php</comment>');
        $this->line('  3. See <comment>INSTALL.md</comment> for WYSIWYG editor integration');
        $this->info('');

        return self::SUCCESS;
    }
}
