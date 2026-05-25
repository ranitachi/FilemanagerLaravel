<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fm_folders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('parent_id')->nullable()->index();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('path');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['parent_id', 'slug']);
            $table->index('path');
            $table->index('owner_id');
        });

        Schema::create('fm_files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->ulid('folder_id')->nullable()->index();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('original_name');
            $table->string('storage_path');
            $table->string('disk', 50)->default('local');
            $table->string('mime_type', 100);
            $table->string('extension', 20);
            $table->unsignedBigInteger('size');
            $table->string('checksum', 64);
            $table->string('thumbnail_path')->nullable();
            $table->json('metadata')->nullable();
            $table->boolean('is_public')->default(false);
            $table->unsignedInteger('download_count')->default(0);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['folder_id', 'created_at']);
            $table->index('owner_id');
            $table->index('mime_type');
            $table->index('checksum');
        });

        Schema::create('fm_file_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('permissionable_id', 26);
            $table->string('permissionable_type');
            $table->unsignedBigInteger('grantable_id');
            $table->string('grantable_type');
            $table->boolean('can_read')->default(true);
            $table->boolean('can_write')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->boolean('can_share')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(
                ['permissionable_id', 'permissionable_type', 'grantable_id', 'grantable_type'],
                'fm_permissions_unique'
            );
            $table->index(['grantable_id', 'grantable_type'], 'fm_permissions_grantable');
        });

        Schema::create('fm_file_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('loggable_id', 26);
            $table->string('loggable_type');
            $table->string('action', 50);
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->ipAddress('ip_address');
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['loggable_id', 'loggable_type'], 'fm_logs_loggable');
            $table->index(['action', 'created_at']);
        });

        Schema::create('fm_file_shares', function (Blueprint $table) {
            $table->id();
            $table->ulid('file_id');
            $table->foreign('file_id')->references('id')->on('fm_files')->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->unsignedInteger('max_downloads')->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->string('password_hash')->nullable();
            $table->timestamps();

            $table->index('token');
            $table->index('file_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fm_file_shares');
        Schema::dropIfExists('fm_file_logs');
        Schema::dropIfExists('fm_file_permissions');
        Schema::dropIfExists('fm_files');
        Schema::dropIfExists('fm_folders');
    }
};
