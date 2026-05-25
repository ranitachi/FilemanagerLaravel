<?php

namespace Fachran\FileManager\Tests;

use Illuminate\Foundation\Auth\User;
use Orchestra\Testbench\TestCase as Orchestra;
use Fachran\FileManager\FileManagerServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [FileManagerServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filemanager.disk', 'local');
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
    }

    protected function createUser(array $attributes = []): User
    {
        return User::create(array_merge([
            'name'     => 'Test User',
            'email'    => 'test@example.com',
            'password' => bcrypt('password'),
        ], $attributes));
    }
}
