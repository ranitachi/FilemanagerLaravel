<?php

namespace Fachran\FileManager\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FolderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'slug'        => $this->slug,
            'path'        => $this->path,
            'description' => $this->description,
            'is_public'   => $this->is_public,
            'files_count' => $this->whenCounted('files'),
            'parent'      => $this->whenLoaded('parent', fn () => [
                'id'   => $this->parent->id,
                'name' => $this->parent->name,
                'path' => $this->parent->path,
            ]),
            'children'    => FolderResource::collection(
                $this->whenLoaded('children')
            ),
            'created_at'  => $this->created_at->toIso8601String(),
        ];
    }
}
