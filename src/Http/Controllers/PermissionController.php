<?php

namespace Fachran\FileManager\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Fachran\FileManager\Models\File;
use Fachran\FileManager\Models\FilePermission;
use Fachran\FileManager\Models\Folder;
use Fachran\FileManager\Services\PermissionService;

class PermissionController extends Controller
{
    public function __construct(protected PermissionService $permissionService) {}

    /**
     * GET /files/{id}/permissions  — list permissions on a file
     * GET /folders/{id}/permissions — list permissions on a folder
     */
    public function index(Request $request, string $type, string $id): JsonResponse
    {
        $resource = $this->resolveResource($type, $id);

        $this->authorizeOwnerOrAdmin($resource);

        $permissions = FilePermission::query()
            ->where('permissionable_id', $resource->getKey())
            ->where('permissionable_type', get_class($resource))
            ->active()
            ->get()
            ->map(fn ($p) => [
                'id'            => $p->id,
                'grantable_id'  => $p->grantable_id,
                'grantable_type'=> $p->grantable_type,
                'can_read'      => $p->can_read,
                'can_write'     => $p->can_write,
                'can_delete'    => $p->can_delete,
                'can_share'     => $p->can_share,
                'expires_at'    => $p->expires_at?->toIso8601String(),
            ]);

        return response()->json(['data' => $permissions]);
    }

    /**
     * POST /files/{id}/permissions
     * POST /folders/{id}/permissions
     *
     * Body:
     * {
     *   "grantable_id":   1,
     *   "grantable_type": "user",   // "user" | "role"
     *   "permissions":    ["read", "write"],
     *   "expires_at":     "2025-12-31T23:59:59Z"  // optional
     * }
     */
    public function store(Request $request, string $type, string $id): JsonResponse
    {
        $resource = $this->resolveResource($type, $id);

        $this->authorizeOwnerOrAdmin($resource);

        $data = $request->validate([
            'grantable_id'   => ['required', 'integer'],
            'grantable_type' => ['required', 'in:user,role'],
            'permissions'    => ['required', 'array'],
            'permissions.*'  => ['in:read,write,delete,share'],
            'expires_at'     => ['nullable', 'date', 'after:now'],
        ]);

        $grantableType = $data['grantable_type'] === 'user'
            ? config('auth.providers.users.model')
            : \Spatie\Permission\Models\Role::class;

        $permission = $this->permissionService->grant(
            resource:      $resource,
            grantableId:   $data['grantable_id'],
            grantableType: $grantableType,
            permissions:   $data['permissions'],
            expiresAt:     isset($data['expires_at']) ? new \DateTime($data['expires_at']) : null,
        );

        return response()->json([
            'message' => 'Permission granted.',
            'data'    => $permission,
        ], 201);
    }

    /**
     * DELETE /permissions/{permissionId}  — revoke a specific permission row
     */
    public function destroy(string $permissionId): JsonResponse
    {
        $permission = FilePermission::findOrFail($permissionId);

        // Must be owner/admin of the resource to revoke
        $resource = $permission->permissionable;
        $this->authorizeOwnerOrAdmin($resource);

        $permission->delete();

        return response()->json(['message' => 'Permission revoked.']);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    protected function resolveResource(string $type, string $id): File|Folder
    {
        return match ($type) {
            'files'   => File::findOrFail($id),
            'folders' => Folder::findOrFail($id),
            default   => abort(404, 'Invalid resource type.'),
        };
    }

    protected function authorizeOwnerOrAdmin(File|Folder $resource): void
    {
        $user = auth()->user();
        $isAdmin = method_exists($user, 'hasRole') && $user->hasRole('super-admin');
        $isOwner = (int) $resource->owner_id === (int) $user->getKey();

        abort_unless($isAdmin || $isOwner, 403, 'Only the owner or admin can manage permissions.');
    }
}
