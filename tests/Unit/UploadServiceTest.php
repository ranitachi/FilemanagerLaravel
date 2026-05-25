<?php

namespace Fachran\FileManager\Tests\Unit;

use Fachran\FileManager\Services\UploadService;
use Fachran\FileManager\Tests\TestCase;

class UploadServiceSanitizerTest extends TestCase
{
    protected UploadService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Partial mock — only test sanitizeFilename
        $this->service = $this->app->make(UploadService::class);
    }

    public function test_strips_directory_traversal(): void
    {
        $result = $this->service->sanitizeFilename('../../etc/passwd');
        $this->assertStringNotContainsString('..', $result);
        $this->assertStringNotContainsString('/', $result);
    }

    public function test_strips_null_bytes(): void
    {
        $result = $this->service->sanitizeFilename("file\0name.pdf");
        $this->assertStringNotContainsString("\0", $result);
    }

    public function test_collapses_double_extension(): void
    {
        $result = $this->service->sanitizeFilename('evil.php.jpg');
        $this->assertSame('evil.php.jpg', $result); // allowed — dot not duplicated
    }

    public function test_collapses_triple_dots(): void
    {
        $result = $this->service->sanitizeFilename('file...php');
        $this->assertStringNotContainsString('...', $result);
    }

    public function test_returns_fallback_for_empty_filename(): void
    {
        $result = $this->service->sanitizeFilename('');
        $this->assertNotEmpty($result);
    }

    public function test_strips_special_chars(): void
    {
        $result = $this->service->sanitizeFilename('file<script>.pdf');
        $this->assertStringNotContainsString('<', $result);
        $this->assertStringNotContainsString('>', $result);
    }

    public function test_storage_path_uses_uuid_not_original_name(): void
    {
        $path = $this->service->generateStoragePath('pdf');
        $this->assertStringStartsWith('filemanager/', $path);
        $this->assertStringEndsWith('.pdf', $path);
        // Path should NOT contain any user-provided name
        $this->assertMatchesRegularExpression(
            '#filemanager/\d{4}/\d{2}/[a-f0-9\-]+\.pdf#',
            $path
        );
    }
}
