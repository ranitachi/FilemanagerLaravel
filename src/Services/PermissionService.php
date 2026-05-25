<?php

namespace Fachran\FileManager\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Fachran\FileManager\Models\File;
use Fachran\FileManager\Models\FilePermission;
use Fachran\FileManager\Models\Folder;

class PermissionService
{
    public const PERM_READ   = 'read';
    public const PERM_WRITE  = 'write';
    public const PERM_DELETE = 'delete';
    public const PERM_SHARE  = 'share';

    /**
     * Resolve whether a user has a permission on a File or Folder.
     *
     * Resolution order:
     *  1. Super Admin role           → always allow
     *  2. Owner of resource          → always allow
     *  3. Explicit User permission   → honor can_X value
     *  4. Role-based permission      → allow if any role has can_X = true
     *  5. Parent Folder permission   → inherited for Files
     *  6. Public flag (read only)    → allow read if is_public = true
     *  7. Default                    → deny
     */
    public function can(
        Authenticatable $user,
        File|Folder $resource,
        string $permission
    ): bool {
        // 1. Super Admin
        if (method_exists($user, 'hasRole') && $user->hasRole('super-admin')) {
            return true;
        }

        // 2. Owner
        if ((int) $resource->owner_id === (int) $user->getKey()) {
            return true;
        }

        // 3. Explicit user permission
        $userPerm = FilePermission::query()
            ->where('permissionable_id', $resource->getKey())
            ->where('permissionable_type', get_class($resource))
            ->where('grantable_id', $user->getKey())
            ->where('grantable_type', get_class($user))
            ->active()
            ->first();

        if ($userPerm) {
            return (bool) $userPerm->{"can_{$permission}"};
        }

        // 4. Role-based permission
        if (method_exists($user, 'roles')) {
            $roleIds = $user->roles()->pluck('id');

            if ($roleIds->isNotEmpty()) {
                $hasRolePerm = FilePermission::query()
                    ->where('permissionable_id', $resource->getKey())
                    ->where('permissionable_type', get_class($resource))
                    ->whereIn('grantable_id', $roleIds)
                    ->active()
                    ->where("can_{$permission}", true)
                    ->exists();

                if ($hasRolePerm) {
                    return true;
                }
            }
        }

        // 5. Folder inheritance for files
        if ($resource instanceof File && $resource->folder_id) {
            $folder = Folder::find($resource->folder_id);
            if ($folder) {
                return $this->can($user, $folder, $permission);
            }
        }

        // 6. Public read
        if ($resource->is_public && $permission === self::PERM_READ) {
            return true;
        }

        // 7. Deny
        return false;
    }

    /**
     * Grant permission to a user or role on a File/Folder.
     */
    public function grant(
        File|Folder $resource,
        int $grantableId,
        string $grantableType,
        array $permissions,
        ?\DateTimeInterface $expiresAt = null
    ): FilePermission {
        return FilePermission::updateOrCreate(
            [
                'permissionable_id'   => $resource->getKey(),
                'permissionable_type' => get_class($resource),
                'grantable_id'        => $grantableId,
                'grantable_type'      => $grantableType,
            ],
            [
                'can_read'   => in_array('read', $permissions),
                'can_write'  => in_array('write', $permissions),
                'can_delete' => in_array('delete', $permissions),
                'can_share'  => in_array('share', $permissions),
                'expires_at' => $expiresAt,
                'created_by' => auth()->id(),
            ]
        );
    }

    /**
     * Revoke all permissions for a user/role on a resource.
     */
    public function revoke(
        File|Folder $resource,
        int $grantableId,
        string $grantableType
    ): bool {
        return (bool) FilePermission::query()
            ->where('permissionable_id', $resource->getKey())
            ->where('permissionable_type', get_class($resource))
            ->where('grantable_id', $grantableId)
            ->where('grantable_type', $grantableType)
            ->delete();
    }
}
