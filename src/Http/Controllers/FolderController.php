<?php

namespace Fachran\FileManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fachran\FileManager\Http\Resources\FolderResource;
use Fachran\FileManager\Services\FolderService;

class FolderController extends Controller
{
    public function __construct(protected FolderService $folderService) {}

    /** GET /folders — folder tree */
    public function index(Request $request): JsonResponse
    {
        $tree = $this->folderService->tree($request->parent_id);
        return response()->json(['data' => FolderResource::collection($tree)]);
    }

    /** POST /folders */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'parent_id'   => ['nullable', 'string'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_public'   => ['nullable', 'boolean'],
        ]);

        $folder = $this->folderService->create(
            $request->name,
            $request->parent_id,
            $request->only(['description', 'is_public'])
        );

        return (new FolderResource($folder))
            ->response()
            ->setStatusCode(201);
    }

    /** PATCH /folders/{id} */
    public function update(Request $request, string $id): JsonResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        $folder = $this->folderService->rename($id, $request->name);

        return (new FolderResource($folder))->response();
    }

    /** DELETE /folders/{id} */
    public function destroy(string $id): JsonResponse
    {
        $this->folderService->delete($id);
        return response()->json(['message' => 'Folder deleted.']);
    }
}
