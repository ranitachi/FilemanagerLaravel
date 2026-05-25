<?php

namespace Fachran\FileManager\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var \Fachran\FileManager\Models\File $this */
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'original_name' => $this->original_name,
            'mime_type'     => $this->mime_type,
            'extension'     => $this->extension,
            'size'          => $this->size,
            'size_human'    => $this->size_human,
            'is_public'     => $this->is_public,
            'is_image'      => $this->isImage(),
            'is_previewable'=> $this->isPreviewable(),
            'download_count'=> $this->download_count,
            'metadata'      => $this->metadata,
            'thumbnail_url' => $this->thumbnail_url,
            'folder'        => $this->whenLoaded('folder', fn () => [
                'id'   => $this->folder->id,
                'name' => $this->folder->name,
                'path' => $this->folder->path,
            ]),
            'owner'         => $this->whenLoaded('owner', fn () => [
                'id'   => $this->owner->id,
                'name' => $this->owner->name,
            ]),
            'permissions'   => $this->when(
                $request->user(),
                fn () => $this->resolvePermissions($request)
            ),
            'urls' => [
                'download' => route('filemanager.files.download', $this->id),
                'preview'  => route('filemanager.files.preview', $this->id),
            ],
            'created_at'    => $this->created_at->toIso8601String(),
            'updated_at'    => $this->updated_at->toIso8601String(),
        ];
    }

    protected function resolvePermissions(Request $request): array
    {
        $user = $request->user();
        $permService = app(\Fachran\FileManager\Services\PermissionService::class);

        return [
            'can_read'   => $permService->can($user, $this->resource, 'read'),
            'can_write'  => $permService->can($user, $this->resource, 'write'),
            'can_delete' => $permService->can($user, $this->resource, 'delete'),
            'can_share'  => $permService->can($user, $this->resource, 'share'),
        ];
    }
}
