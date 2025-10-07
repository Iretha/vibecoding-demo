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
     * Get user's roles with pagination.
     */
    public function getUserRoles(User $user, int $page = 1, int $limit = 20, string $sort = 'role_name'): array
    {
        $query = $user->roles()
            ->orderBy($sort);

        $total = $query->count();
        $roles = $query->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->map(function ($role) {
                return [
                    'role_id' => $role->id,
                    'role_name' => $role->role_name,
                    'assigned_at' => $role->pivot->created_at->toIso8601String(),
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
