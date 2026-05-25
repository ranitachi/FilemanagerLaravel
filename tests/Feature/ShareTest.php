<?php

namespace Fachran\FileManager\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Fachran\FileManager\Models\File;
use Fachran\FileManager\Models\FileShare;
use Fachran\FileManager\Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_share_link(): void
    {
        Storage::fake('local');
        $user = $this->createUser();
        $file = $this->uploadFileAs($user);

        $this->actingAs($user)
            ->postJson("/api/v1/filemanager/files/{$file->id}/share", [
                'expires_in_minutes' => 60,
                'max_downloads'      => 5,
            ])
            ->assertStatus(201)
            ->assertJsonStructure(['data' => ['share_url', 'token', 'expires_at']]);
    }

    public function test_share_link_download_works(): void
    {
        Storage::fake('local');
        $user  = $this->createUser();
        $file  = $this->uploadFileAs($user);

        $share = FileShare::create([
            'file_id'        => $file->id,
            'token'          => 'test-token-abcdef1234567890',
            'created_by'     => $user->id,
            'expires_at'     => now()->addHour(),
            'download_count' => 0,
        ]);

        // Public download — no auth needed
        $this->get("/filemanager/share/{$share->token}/download")
            ->assertStatus(200);
    }

    public function test_expired_share_link_is_rejected(): void
    {
        Storage::fake('local');
        $user  = $this->createUser();
        $file  = $this->uploadFileAs($user);

        $share = FileShare::create([
            'file_id'    => $file->id,
            'token'      => 'expired-token-xyz',
            'created_by' => $user->id,
            'expires_at' => now()->subHour(), // already expired
        ]);

        $this->get("/filemanager/share/{$share->token}/download")
            ->assertStatus(410);
    }

    public function test_download_limit_is_enforced(): void
    {
        Storage::fake('local');
        $user  = $this->createUser();
        $file  = $this->uploadFileAs($user);

        $share = FileShare::create([
            'file_id'        => $file->id,
            'token'          => 'limited-token-xyz',
            'created_by'     => $user->id,
            'expires_at'     => now()->addHour(),
            'max_downloads'  => 2,
            'download_count' => 2, // already at limit
        ]);

        $this->get("/filemanager/share/{$share->token}/download")
            ->assertStatus(410);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function uploadFileAs($user): File
    {
        $uploaded = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)
            ->postJson('/api/v1/filemanager/upload', ['file' => $uploaded]);

        return File::find($response->json('data.id'));
    }
}
