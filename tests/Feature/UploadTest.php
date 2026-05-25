<?php

namespace Fachran\FileManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Fachran\FileManager\Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_file(): void
    {
        Storage::fake('local');
        $user = $this->createUser();

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/filemanager/upload', ['file' => $file]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id', 'name', 'mime_type', 'size', 'size_human',
                    'urls' => ['download', 'preview'],
                ],
            ]);

        $this->assertDatabaseHas('fm_files', [
            'original_name' => 'document.pdf',
            'owner_id'      => $user->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_upload(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->postJson('/api/v1/filemanager/upload', ['file' => $file])
            ->assertStatus(401);
    }

    public function test_disallowed_mime_type_is_rejected(): void
    {
        Storage::fake('local');
        $user = $this->createUser();

        // Try uploading a .php file
        $file = UploadedFile::fake()->create('shell.php', 1, 'application/x-php');

        $this->actingAs($user)
            ->postJson('/api/v1/filemanager/upload', ['file' => $file])
            ->assertStatus(422);
    }

    public function test_oversized_file_is_rejected(): void
    {
        Storage::fake('local');
        $user = $this->createUser();

        // 20MB file — over the 10MB default limit
        $file = UploadedFile::fake()->create('big.pdf', 20480, 'application/pdf');

        $this->actingAs($user)
            ->postJson('/api/v1/filemanager/upload', ['file' => $file])
            ->assertStatus(422);
    }

    public function test_upload_saves_audit_log(): void
    {
        Storage::fake('local');
        $user = $this->createUser();

        $file = UploadedFile::fake()->create('report.pdf', 200, 'application/pdf');

        $this->actingAs($user)
            ->postJson('/api/v1/filemanager/upload', ['file' => $file]);

        $this->assertDatabaseHas('fm_file_logs', [
            'user_id' => $user->id,
            'action'  => 'upload',
        ]);
    }

    public function test_filename_sanitization_strips_path_traversal(): void
    {
        Storage::fake('local');
        $user = $this->createUser();

        $file = UploadedFile::fake()->createWithContent(
            '../../etc/passwd.pdf',
            '%PDF-1.4 fake pdf content'
        );

        $response = $this->actingAs($user)
            ->postJson('/api/v1/filemanager/upload', ['file' => $file]);

        if ($response->status() === 201) {
            // The stored name must NOT contain path traversal
            $name = $response->json('data.name');
            $this->assertStringNotContainsString('..', $name);
            $this->assertStringNotContainsString('/', $name);
        }
    }
}
