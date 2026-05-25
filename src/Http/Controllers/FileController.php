<?php

namespace Fachran\FileManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fachran\FileManager\Http\Requests\UploadFileRequest;
use Fachran\FileManager\Http\Resources\FileResource;
use Fachran\FileManager\Services\FileService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    public function __construct(protected FileService $fileService) {}

    /**
     * GET /files  — browse files in a folder
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'folder_id' => ['nullable', 'string'],
            'per_page'  => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort'      => ['nullable', 'in:name,size,created_at,extension'],
            'order'     => ['nullable', 'in:asc,desc'],
            'search'    => ['nullable', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string'],
        ]);

        $files = $this->fileService->browse($request->folder_id, $request->only([
            'per_page', 'sort', 'order', 'search', 'mime_type',
        ]));

        return FileResource::collection($files)->response();
    }

    /**
     * POST /upload  — upload file
     */
    public function upload(UploadFileRequest $request): JsonResponse
    {
        $file = $this->fileService->upload(
            $request->file('file'),
            $request->folder_id
        );

        return (new FileResource($file))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * GET /files/{id}  — get file metadata
     */
    public function show(string $id): JsonResponse
    {
        $file = $this->fileService->findOrFail($id);
        return (new FileResource($file))->response();
    }

    /**
     * GET /files/{id}/download
     */
    public function download(string $id): StreamedResponse
    {
        return $this->fileService->download($id);
    }

    /**
     * GET /files/{id}/preview
     */
    public function preview(string $id): StreamedResponse|JsonResponse
    {
        $file = $this->fileService->findOrFail($id);

        if (! $file->isPreviewable()) {
            return response()->json([
                'previewable' => false,
                'data' => new FileResource($file),
            ]);
        }

        return $this->fileService->preview($id);
    }

    /**
     * PATCH /files/{id}/rename
     */
    public function rename(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $file = $this->fileService->rename($id, $request->name);

        return (new FileResource($file))->response();
    }

    /**
     * POST /files/{id}/move
     */
    public function move(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'folder_id' => ['nullable', 'string'],
        ]);

        $file = $this->fileService->move($id, $request->folder_id);

        return (new FileResource($file))->response();
    }

    /**
     * POST /files/{id}/copy
     */
    public function copy(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'folder_id' => ['nullable', 'string'],
        ]);

        $file = $this->fileService->copy($id, $request->folder_id);

        return (new FileResource($file))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * DELETE /files/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'permanent' => ['nullable', 'boolean'],
        ]);

        $this->fileService->delete($id, (bool) $request->boolean('permanent'));

        return response()->json([
            'message' => $request->boolean('permanent')
                ? 'File permanently deleted.'
                : 'File moved to trash.',
        ]);
    }

    /**
     * POST /files/{id}/restore
     */
    public function restore(string $id): JsonResponse
    {
        $file = $this->fileService->restore($id);
        return (new FileResource($file))->response();
    }
}
