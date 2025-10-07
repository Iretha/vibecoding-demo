<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserRolesService
{
    private const MAX_ROLES_PER_USER = 20;


    /**
     * Add multiple roles to a user.
     */
    public function addRolesToUser(User $user, array $roleNames, ?Request $request = null): array
    {
        $roleNames = $this->normalizeRoleNames($roleNames);
        $this->validateRoleNames($roleNames);
        $this->validateRoleLimit($user, count($roleNames));

        $added = [];
        $skipped = [];

        DB::transaction(function () use ($user, $roleNames, &$added, &$skipped) {
            foreach ($roleNames as $roleName) {
                // Check if user already has this role
                if ($user->hasRole($roleName)) {
                    $role = Role::where('role_name', $roleName)->first();
                    $skipped[] = [
                        'role_id' => $role->id,
                        'role_name' => $roleName,
                        'reason' => 'User already has this role',
                    ];
                    continue;
                }

                // Find or create the role
                $role = Role::firstOrCreate(['role_name' => $roleName]);

                // Create the user-role association
                UserRole::create([
                    'user_id' => $user->id,
                    'role_id' => $role->id,
                ]);

                $added[] = [
                    'role_id' => $role->id,
                    'role_name' => $roleName,
                ];
            }
        });

        $result = [
            'added' => $added,
            'skipped' => $skipped,
        ];

        // Note: Audit logging is handled in the controller

        return $result;
    }

    /**
     * Add a single role to a user.
     */
    public function addSingleRoleToUser(User $user, string $roleName, ?Request $request = null): array
    {
        return $this->addRolesToUser($user, [$roleName], $request);
    }

    /**
     * Get user's roles with pagination and custom ordering.
     */
    public function getUserRoles(User $user, int $page = 1, int $limit = 20, string $sort = 'role_name'): array
    {
        // Use custom ordering: display_order first, then role_name
        $query = $user->roles()
            ->select('roles.*', 'user_roles.display_order', 'user_roles.created_at as pivot_created_at')
            ->orderByRaw('CASE WHEN user_roles.display_order IS NULL THEN 1 ELSE 0 END')
            ->orderBy('user_roles.display_order', 'ASC')
            ->orderBy('roles.role_name', 'ASC');

        $total = $query->count();
        $roles = $query->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(function ($role) {
                return [
                    'role_id' => $role->id,
                    'role_name' => $role->role_name,
                    'display_order' => $role->display_order,
                    'assigned_at' => $role->pivot_created_at ? \Carbon\Carbon::parse($role->pivot_created_at)->toIso8601String() : null,
                ];
            });

        return [
            'roles' => $roles,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * Remove a role from a user.
     */
    public function removeRoleFromUser(User $user, int $roleId, ?Request $request = null): bool
    {
        $userRole = UserRole::where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->with('role')
            ->first();

        if (!$userRole) {
            return false;
        }

        $roleName = $userRole->role->role_name;
        $userRole->delete();

        // Note: Audit logging is handled in the controller

        return true;
    }

    /**
     * Reorder user's roles.
     */
    public function reorderUserRoles(User $user, array $roleOrders, ?Request $request = null): array
    {
        // Validate that all role_ids belong to the user
        $userRoleIds = $user->roles()->pluck('roles.id')->toArray();
        $requestedRoleIds = array_column($roleOrders, 'role_id');
        
        $invalidRoleIds = array_diff($requestedRoleIds, $userRoleIds);
        if (!empty($invalidRoleIds)) {
            throw new \InvalidArgumentException('Some roles do not belong to this user: ' . implode(', ', $invalidRoleIds));
        }

        // Validate display_order values
        foreach ($roleOrders as $roleOrder) {
            if (!isset($roleOrder['role_id']) || !isset($roleOrder['display_order'])) {
                throw new \InvalidArgumentException('Each role order must have role_id and display_order');
            }
            if (!is_int($roleOrder['display_order']) || $roleOrder['display_order'] < 1) {
                throw new \InvalidArgumentException('display_order must be a positive integer');
            }
        }

        $updatedCount = 0;
        DB::transaction(function () use ($user, $roleOrders, &$updatedCount) {
            foreach ($roleOrders as $roleOrder) {
                UserRole::where('user_id', $user->id)
                    ->where('role_id', $roleOrder['role_id'])
                    ->update(['display_order' => $roleOrder['display_order']]);
                $updatedCount++;
            }
        });

        return [
            'updated_count' => $updatedCount,
        ];
    }

    /**
     * Set display order for a single role.
     */
    public function setRoleOrder(User $user, int $roleId, int $displayOrder, ?Request $request = null): bool
    {
        // Validate that the role belongs to the user
        $userRole = UserRole::where('user_id', $user->id)
            ->where('role_id', $roleId)
            ->first();

        if (!$userRole) {
            throw new \InvalidArgumentException('Role does not belong to this user');
        }

        if ($displayOrder < 1) {
            throw new \InvalidArgumentException('display_order must be a positive integer');
        }

        $userRole->update(['display_order' => $displayOrder]);
        return true;
    }

    /**
     * Check if user can manage roles for the given user ID.
     */
    public function canManageUserRoles(User $authenticatedUser, int $targetUserId): bool
    {
        // Users can only manage their own roles
        return $authenticatedUser->id === $targetUserId;
    }

    /**
     * Normalize role names (trim, title case).
     */
    private function normalizeRoleNames(array $roleNames): array
    {
        return array_map(function ($roleName) {
            return Str::title(trim($roleName));
        }, array_filter($roleNames, function ($roleName) {
            return !empty(trim($roleName));
        }));
    }

    /**
     * Validate role names.
     */
    private function validateRoleNames(array $roleNames): void
    {
        foreach ($roleNames as $roleName) {
            if (strlen($roleName) > 100) {
                throw new \InvalidArgumentException('Role name cannot exceed 100 characters');
            }
        }
    }

    /**
     * Validate role limit per user.
     */
    private function validateRoleLimit(User $user, int $newRolesCount): void
    {
        $currentRoleCount = $user->roles()->count();
        if ($currentRoleCount + $newRolesCount > self::MAX_ROLES_PER_USER) {
            throw new \InvalidArgumentException(
                "Cannot add {$newRolesCount} roles. User already has {$currentRoleCount} roles. Maximum allowed is " . self::MAX_ROLES_PER_USER
            );
        }
    }
}
