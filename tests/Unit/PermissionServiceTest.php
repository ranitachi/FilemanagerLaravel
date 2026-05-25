<?php

namespace Fachran\FileManager\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Fachran\FileManager\Models\File;
use Fachran\FileManager\Models\FilePermission;
use Fachran\FileManager\Services\PermissionService;
use Fachran\FileManager\Tests\TestCase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(PermissionService::class);
    }

    public function test_owner_always_has_full_access(): void
    {
        $user = $this->createUser();
        $file = File::factory()->create(['owner_id' => $user->id, 'created_by' => $user->id]);

        $this->assertTrue($this->service->can($user, $file, 'read'));
        $this->assertTrue($this->service->can($user, $file, 'write'));
        $this->assertTrue($this->service->can($user, $file, 'delete'));
        $this->assertTrue($this->service->can($user, $file, 'share'));
    }

    public function test_non_owner_is_denied_by_default(): void
    {
        $owner = $this->createUser(['email' => 'owner@test.com']);
        $other = $this->createUser(['email' => 'other@test.com']);
        $file  = File::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id]);

        $this->assertFalse($this->service->can($other, $file, 'read'));
        $this->assertFalse($this->service->can($other, $file, 'write'));
    }

    public function test_explicit_read_permission_grants_access(): void
    {
        $owner = $this->createUser(['email' => 'owner@test.com']);
        $guest = $this->createUser(['email' => 'guest@test.com']);
        $file  = File::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id]);

        FilePermission::create([
            'permissionable_id'   => $file->id,
            'permissionable_type' => File::class,
            'grantable_id'        => $guest->id,
            'grantable_type'      => get_class($guest),
            'can_read'            => true,
            'can_write'           => false,
            'can_delete'          => false,
            'can_share'           => false,
            'created_by'          => $owner->id,
        ]);

        $this->assertTrue($this->service->can($guest, $file, 'read'));
        $this->assertFalse($this->service->can($guest, $file, 'write'));
        $this->assertFalse($this->service->can($guest, $file, 'delete'));
    }

    public function test_expired_permission_is_ignored(): void
    {
        $owner = $this->createUser(['email' => 'owner@test.com']);
        $guest = $this->createUser(['email' => 'guest@test.com']);
        $file  = File::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id]);

        FilePermission::create([
            'permissionable_id'   => $file->id,
            'permissionable_type' => File::class,
            'grantable_id'        => $guest->id,
            'grantable_type'      => get_class($guest),
            'can_read'            => true,
            'can_write'           => true,
            'can_delete'          => true,
            'can_share'           => true,
            'expires_at'          => now()->subHour(), // expired!
            'created_by'          => $owner->id,
        ]);

        $this->assertFalse($this->service->can($guest, $file, 'read'));
    }

    public function test_public_file_allows_anonymous_read(): void
    {
        $owner = $this->createUser();
        $guest = $this->createUser(['email' => 'guest@test.com']);

        $file = File::factory()->create([
            'owner_id'   => $owner->id,
            'created_by' => $owner->id,
            'is_public'  => true,
        ]);

        $this->assertTrue($this->service->can($guest, $file, 'read'));
        $this->assertFalse($this->service->can($guest, $file, 'write')); // public != write access
    }

    public function test_grant_creates_permission(): void
    {
        $owner = $this->createUser(['email' => 'owner@test.com']);
        $guest = $this->createUser(['email' => 'guest@test.com']);
        $file  = File::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id]);

        $this->actingAs($owner);

        $this->service->grant(
            resource:      $file,
            grantableId:   $guest->id,
            grantableType: get_class($guest),
            permissions:   ['read', 'write'],
        );

        $this->assertTrue($this->service->can($guest, $file, 'read'));
        $this->assertTrue($this->service->can($guest, $file, 'write'));
        $this->assertFalse($this->service->can($guest, $file, 'delete'));
    }

    public function test_revoke_removes_permission(): void
    {
        $owner = $this->createUser(['email' => 'owner@test.com']);
        $guest = $this->createUser(['email' => 'guest@test.com']);
        $file  = File::factory()->create(['owner_id' => $owner->id, 'created_by' => $owner->id]);

        $this->actingAs($owner);

        $this->service->grant($file, $guest->id, get_class($guest), ['read']);
        $this->assertTrue($this->service->can($guest, $file, 'read'));

        $this->service->revoke($file, $guest->id, get_class($guest));
        $this->assertFalse($this->service->can($guest, $file, 'read'));
    }
}
