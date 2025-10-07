<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use Illuminate\Database\Eloquent\Collection;

class RolesService
{
    /**
     * Get all available roles with optional search and pagination.
     */
    public function getAllRoles(string $search = '', int $limit = 50): array
    {
        $query = Role::withCount('users');

        if (!empty($search)) {
            $query->search($search);
        }

        $roles = $query->orderBy('role_name')
            ->limit($limit)
            ->get()
            ->map(function ($role) {
                return [
                    'role_id' => $role->id,
                    'role_name' => $role->role_name,
                    'user_count' => $role->users_count,
                ];
            });

        $total = Role::count();

        return [
            'roles' => $roles,
            'total' => $total,
        ];
    }

    /**
     * Get a specific role by ID.
     */
    public function getRoleById(int $roleId): ?Role
    {
        return Role::withCount('users')->find($roleId);
    }

    /**
     * Get a specific role by name.
     */
    public function getRoleByName(string $roleName): ?Role
    {
        return Role::where('role_name', $roleName)->first();
    }

    /**
     * Get popular roles (most used).
     */
    public function getPopularRoles(int $limit = 10): Collection
    {
        return Role::withCount('users')
            ->orderByDesc('users_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all users with a specific role.
     */
    public function getUsersWithRole(int $roleId, int $page = 1, int $limit = 20): array
    {
        $role = Role::with(['users' => function ($query) use ($page, $limit) {
            $query->offset(($page - 1) * $limit)->limit($limit);
        }])->find($roleId);

        if (!$role) {
            return [
                'users' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
            ];
        }

        $total = $role->users()->count();
        $users = $role->users->map(function ($user) {
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'assigned_at' => $user->pivot->created_at->toIso8601String(),
            ];
        });

        return [
            'users' => $users,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ];
    }
}
