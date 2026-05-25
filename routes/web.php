<?php

use Illuminate\Support\Facades\Route;
use Fachran\FileManager\Http\Controllers\EditorCallbackController;
use Fachran\FileManager\Http\Controllers\FileController;
use Fachran\FileManager\Http\Controllers\ShareController;

$prefix         = config('filemanager.route_prefix', 'filemanager');
$webMiddleware  = config('filemanager.middleware.web', ['web', 'auth']);

// ── Authenticated: File Download & Preview (web) ──────────────────────────────
Route::prefix($prefix)
    ->middleware($webMiddleware)
    ->name('filemanager.')
    ->group(function () {
        Route::get('/files/{id}/download', [FileController::class, 'download'])->name('files.download');
        Route::get('/files/{id}/preview',  [FileController::class, 'preview'])->name('files.preview');

        // ── WYSIWYG Picker ───────────────────────────────────────────────────
        Route::get('/picker',                  [EditorCallbackController::class, 'picker'])->name('picker');
        Route::get('/picker/select/{fileId}',  [EditorCallbackController::class, 'select'])->name('picker.select');
    });

// ── Public: Share Links (no auth) ────────────────────────────────────────────
Route::prefix($prefix)
    ->middleware(['web'])
    ->name('filemanager.')
    ->group(function () {
        Route::get('/share/{token}',          [ShareController::class, 'show'])->name('share.show');
        Route::get('/share/{token}/download', [ShareController::class, 'download'])->name('share.download');
    });

// ── Signed URL Serving (local storage) ───────────────────────────────────────
Route::get('/filemanager/serve', function (\Illuminate\Http\Request $request) {
    if (! $request->hasValidSignature()) {
        abort(401, 'Link has expired or is invalid.');
    }

    $path = base64_decode($request->query('path'));
    $disk = \Illuminate\Support\Facades\Storage::disk(config('filemanager.disk', 'local'));

    if (! $disk->exists($path)) {
        abort(404);
    }

    return response()->stream(function () use ($disk, $path) {
        echo $disk->get($path);
    }, 200, [
        'Content-Type'  => $disk->mimeType($path),
        'Cache-Control' => 'private, max-age=3600',
    ]);
})->name('filemanager.files.serve');
