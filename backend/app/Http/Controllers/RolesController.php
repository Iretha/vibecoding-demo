<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\RolesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RolesController extends Controller
{
    public function __construct(
        private RolesService $rolesService
    ) {}

    /**
     * Get all available roles.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'search' => 'string|max:100',
                'limit' => 'integer|min:1|max:100',
            ]);

            $search = $request->get('search', '');
            $limit = (int) $request->get('limit', 50);

            $result = $this->rolesService->getAllRoles($search, $limit);

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200)->header('X-Request-ID', (string) Str::uuid());

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'ROLES_002',
            ], 422)->header('X-Request-ID', (string) Str::uuid());

        } catch (\Exception $e) {
            Log::error('Error getting all roles', [
                'search' => $request->get('search', ''),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving roles',
                'error_code' => 'ROLES_004',
            ], 500)->header('X-Request-ID', (string) Str::uuid());
        }
    }

    /**
     * Get a specific role by ID.
     */
    public function show(Request $request, int $roleId): JsonResponse
    {
        try {
            $role = $this->rolesService->getRoleById($roleId);

            if (!$role) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found',
                    'error_code' => 'ROLES_005',
                ], 404)->header('X-Request-ID', (string) Str::uuid());
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->role_name,
                    'user_count' => $role->user_count,
                    'created_at' => $role->created_at->toIso8601String(),
                    'updated_at' => $role->updated_at->toIso8601String(),
                ],
            ], 200)->header('X-Request-ID', (string) Str::uuid());

        } catch (\Exception $e) {
            Log::error('Error getting role by ID', [
                'role_id' => $roleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving role',
                'error_code' => 'ROLES_004',
            ], 500)->header('X-Request-ID', (string) Str::uuid());
        }
    }

    /**
     * Get popular roles.
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'limit' => 'integer|min:1|max:50',
            ]);

            $limit = (int) $request->get('limit', 10);
            $roles = $this->rolesService->getPopularRoles($limit);

            $formattedRoles = $roles->map(function ($role) {
                return [
                    'role_id' => $role->id,
                    'role_name' => $role->role_name,
                    'user_count' => $role->users_count,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'roles' => $formattedRoles,
                    'total' => $formattedRoles->count(),
                ],
            ], 200)->header('X-Request-ID', (string) Str::uuid());

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'ROLES_002',
            ], 422)->header('X-Request-ID', (string) Str::uuid());

        } catch (\Exception $e) {
            Log::error('Error getting popular roles', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving popular roles',
                'error_code' => 'ROLES_004',
            ], 500)->header('X-Request-ID', (string) Str::uuid());
        }
    }

    /**
     * Get all users with a specific role.
     */
    public function users(Request $request, int $roleId): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100',
            ]);

            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 20);

            $result = $this->rolesService->getUsersWithRole($roleId, $page, $limit);

            if (empty($result['users']) && $page === 1) {
                // Check if role exists
                $role = $this->rolesService->getRoleById($roleId);
                if (!$role) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Role not found',
                        'error_code' => 'ROLES_005',
                    ], 404)->header('X-Request-ID', (string) Str::uuid());
                }
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200)->header('X-Request-ID', (string) Str::uuid());

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'ROLES_002',
            ], 422)->header('X-Request-ID', (string) Str::uuid());

        } catch (\Exception $e) {
            Log::error('Error getting users with role', [
                'role_id' => $roleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving users with role',
                'error_code' => 'ROLES_004',
            ], 500)->header('X-Request-ID', (string) Str::uuid());
        }
    }
}
