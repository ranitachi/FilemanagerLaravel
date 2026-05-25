<?php

namespace Fachran\FileManager;

use Illuminate\Support\ServiceProvider;
use Fachran\FileManager\Facades\FileManager;
use Fachran\FileManager\Repositories\Contracts\FileRepositoryInterface;
use Fachran\FileManager\Repositories\Contracts\FolderRepositoryInterface;
use Fachran\FileManager\Repositories\FileRepository;
use Fachran\FileManager\Repositories\FolderRepository;
use Fachran\FileManager\Services\AuditLogService;
use Fachran\FileManager\Services\FileService;
use Fachran\FileManager\Services\FolderService;
use Fachran\FileManager\Services\PermissionService;
use Fachran\FileManager\Services\ShareService;
use Fachran\FileManager\Services\ThumbnailService;
use Fachran\FileManager\Services\UploadService;
use Fachran\FileManager\Console\Commands\InstallCommand;
use Fachran\FileManager\Console\Commands\PurgeTrashCommand;
use Fachran\FileManager\Storage\Contracts\StorageAdapterInterface;

class FileManagerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/filemanager.php', 'filemanager');

        $this->bindStorageAdapter();
        $this->bindRepositories();
        $this->bindServices();
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'filemanager');

        $this->registerPublishables();
        $this->registerEventListeners();
        $this->registerCommands();
    }

    protected function bindStorageAdapter(): void
    {
        $this->app->bind(StorageAdapterInterface::class, function ($app) {
            $adapterClass = config('filemanager.storage_adapter');
            return $app->make($adapterClass);
        });
    }

    protected function bindRepositories(): void
    {
        $this->app->bind(FileRepositoryInterface::class, FileRepository::class);
        $this->app->bind(FolderRepositoryInterface::class, FolderRepository::class);
    }

    protected function bindServices(): void
    {
        $this->app->singleton(AuditLogService::class);
        $this->app->singleton(PermissionService::class);
        $this->app->singleton(ThumbnailService::class);

        $this->app->singleton(UploadService::class, function ($app) {
            return new UploadService(
                $app->make(StorageAdapterInterface::class),
                $app->make(FileRepositoryInterface::class),
                $app->make(ThumbnailService::class),
                $app->make(AuditLogService::class),
            );
        });

        $this->app->singleton(FileService::class, function ($app) {
            return new FileService(
                $app->make(FileRepositoryInterface::class),
                $app->make(UploadService::class),
                $app->make(PermissionService::class),
                $app->make(AuditLogService::class),
                $app->make(StorageAdapterInterface::class),
            );
        });

        $this->app->singleton(FolderService::class, function ($app) {
            return new FolderService(
                $app->make(FolderRepositoryInterface::class),
                $app->make(PermissionService::class),
                $app->make(AuditLogService::class),
            );
        });

        $this->app->singleton(ShareService::class, function ($app) {
            return new ShareService(
                $app->make(FileRepositoryInterface::class),
                $app->make(PermissionService::class),
                $app->make(StorageAdapterInterface::class),
            );
        });
    }

    protected function registerPublishables(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/filemanager.php' => config_path('filemanager.php'),
        ], 'filemanager-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'filemanager-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/filemanager'),
        ], 'filemanager-views');

        $this->publishes([
            __DIR__.'/../resources/assets' => public_path('vendor/filemanager'),
        ], 'filemanager-assets');

        // Publish everything at once
        $this->publishes([
            __DIR__.'/../config/filemanager.php'   => config_path('filemanager.php'),
            __DIR__.'/../database/migrations'       => database_path('migrations'),
            __DIR__.'/../resources/views'           => resource_path('views/vendor/filemanager'),
            __DIR__.'/../resources/assets'          => public_path('vendor/filemanager'),
        ], 'filemanager');
    }

    protected function registerCommands(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            PurgeTrashCommand::class,
        ]);
    }

    protected function registerEventListeners(): void
    {
        $listeners = config('filemanager.listen', []);

        foreach ($listeners as $event => $eventListeners) {
            foreach ($eventListeners as $listener) {
                $this->app['events']->listen($event, $listener);
            }
        }
    }
}
