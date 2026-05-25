<?php

use Illuminate\Support\Facades\Route;
use Fachran\FileManager\Http\Controllers\FileController;
use Fachran\FileManager\Http\Controllers\FolderController;
use Fachran\FileManager\Http\Controllers\PermissionController;
use Fachran\FileManager\Http\Controllers\ShareController;
use Fachran\FileManager\Http\Middleware\RateLimitUpload;

$prefix     = config('filemanager.route_prefix', 'filemanager');
$middleware = config('filemanager.middleware.api', ['api', 'auth:sanctum']);

Route::prefix("api/v1/{$prefix}")
    ->middleware($middleware)
    ->name('filemanager.api.')
    ->group(function () {

        // ── Files ──────────────────────────────────────────────────────────
        Route::get('/files',          [FileController::class, 'index'])->name('files.index');
        Route::post('/upload',        [FileController::class, 'upload'])
             ->middleware(RateLimitUpload::class)
             ->name('files.upload');
        Route::get('/files/{id}',          [FileController::class, 'show'])->name('files.show');
        Route::patch('/files/{id}/rename', [FileController::class, 'rename'])->name('files.rename');
        Route::post('/files/{id}/move',    [FileController::class, 'move'])->name('files.move');
        Route::post('/files/{id}/copy',    [FileController::class, 'copy'])->name('files.copy');
        Route::post('/files/{id}/restore', [FileController::class, 'restore'])->name('files.restore');
        Route::delete('/files/{id}',       [FileController::class, 'destroy'])->name('files.destroy');

        // ── Folders ─────────────────────────────────────────────────────────
        Route::get('/folders',        [FolderController::class, 'index'])->name('folders.index');
        Route::post('/folders',       [FolderController::class, 'store'])->name('folders.store');
        Route::patch('/folders/{id}', [FolderController::class, 'update'])->name('folders.update');
        Route::delete('/folders/{id}',[FolderController::class, 'destroy'])->name('folders.destroy');

        // ── Share Links ──────────────────────────────────────────────────────
        Route::post('/files/{id}/share',       [ShareController::class, 'store'])->name('files.share');
        Route::delete('/share/{token}/revoke', [ShareController::class, 'revoke'])->name('share.revoke');

        // ── Permissions (RBAC) ──────────────────────────────────────────────
        Route::get('/{type}/{id}/permissions',  [PermissionController::class, 'index'])->name('permissions.index')
             ->where('type', 'files|folders');
        Route::post('/{type}/{id}/permissions', [PermissionController::class, 'store'])->name('permissions.store')
             ->where('type', 'files|folders');
        Route::delete('/permissions/{permId}',  [PermissionController::class, 'destroy'])->name('permissions.destroy');
    });
