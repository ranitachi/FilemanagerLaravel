<?php

namespace Fachran\FileManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Fachran\FileManager\Tests\TestCase;

class FolderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_folder(): void
    {
        $user = $this->createUser();

        $this->actingAs($user)
            ->postJson('/api/v1/filemanager/folders', [
                'name'      => 'My Documents',
                'is_public' => false,
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'My Documents')
            ->assertJsonPath('data.slug', 'my-documents');
    }

    public function test_folder_path_is_auto_generated(): void
    {
        $user = $this->createUser();

        $parent = $this->actingAs($user)
            ->postJson('/api/v1/filemanager/folders', ['name' => 'Root Folder'])
            ->assertStatus(201)
            ->json('data');

        $child = $this->actingAs($user)
            ->postJson('/api/v1/filemanager/folders', [
                'name'      => 'Child Folder',
                'parent_id' => $parent['id'],
            ])
            ->assertStatus(201)
            ->json('data');

        $this->assertStringContainsString('root-folder', $child['path']);
        $this->assertStringContainsString('child-folder', $child['path']);
    }

    public function test_user_can_rename_own_folder(): void
    {
        $user = $this->createUser();

        $folder = $this->actingAs($user)
            ->postJson('/api/v1/filemanager/folders', ['name' => 'Old Name'])
            ->json('data');

        $this->actingAs($user)
            ->patchJson("/api/v1/filemanager/folders/{$folder['id']}", ['name' => 'New Name'])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'New Name');
    }

    public function test_user_cannot_rename_other_users_folder(): void
    {
        $owner = $this->createUser(['email' => 'owner@test.com']);
        $other = $this->createUser(['email' => 'other@test.com']);

        $folder = $this->actingAs($owner)
            ->postJson('/api/v1/filemanager/folders', ['name' => 'Private'])
            ->json('data');

        $this->actingAs($other)
            ->patchJson("/api/v1/filemanager/folders/{$folder['id']}", ['name' => 'Hacked'])
            ->assertStatus(403);
    }

    public function test_user_can_delete_own_folder(): void
    {
        $user = $this->createUser();

        $folder = $this->actingAs($user)
            ->postJson('/api/v1/filemanager/folders', ['name' => 'To Delete'])
            ->json('data');

        $this->actingAs($user)
            ->deleteJson("/api/v1/filemanager/folders/{$folder['id']}")
            ->assertStatus(200);

        $this->assertSoftDeleted('fm_folders', ['id' => $folder['id']]);
    }
}
