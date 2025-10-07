<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserRolesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UserRolesController extends Controller
{
    public function __construct(
        private UserRolesService $userRolesService
    ) {}

    /**
     * Add multiple roles to a user.
     */
    public function addRoles(Request $request, int $userId): JsonResponse
    {
        try {
            $request->validate([
                'roles' => 'required|array|min:1|max:10',
                'roles.*' => 'required|string|max:100',
            ]);

            $user = User::findOrFail($userId);
            
            // Check if user can manage roles for this user
            if (!$this->userRolesService->canManageUserRoles($request->user(), $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to manage roles for this user',
                    'error_code' => 'ROLES_001',
                ], 403)->header('X-Request-ID', (string) Str::uuid());
            }

            $result = $this->userRolesService->addRolesToUser($user, $request->roles, $request);

            // Log the action
            Log::info('User roles added', [
                'user_id' => $userId,
                'added_roles' => $result['added'],
                'skipped_roles' => $result['skipped'],
                'requested_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Roles processed successfully',
                'data' => $result,
            ], 201)->header('X-Request-ID', (string) Str::uuid());

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'ROLES_002',
            ], 422)->header('X-Request-ID', (string) Str::uuid());

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'ROLES_003',
            ], 400)->header('X-Request-ID', (string) Str::uuid());

        } catch (\Exception $e) {
            Log::error('Error adding user roles', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding roles',
                'error_code' => 'ROLES_004',
            ], 500)->header('X-Request-ID', (string) Str::uuid());
        }
    }

    /**
     * Add a single role to a user.
     */
    public function addSingleRole(Request $request, int $userId): JsonResponse
    {
        try {
            $request->validate([
                'role_name' => 'required|string|max:100',
            ]);

            $user = User::findOrFail($userId);
            
            // Check if user can manage roles for this user
            if (!$this->userRolesService->canManageUserRoles($request->user(), $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to manage roles for this user',
                    'error_code' => 'ROLES_001',
                ], 403)->header('X-Request-ID', (string) Str::uuid());
            }

            $result = $this->userRolesService->addSingleRoleToUser($user, $request->role_name, $request);

            // Log the action
            Log::info('Single role added', [
                'user_id' => $userId,
                'role_name' => $request->role_name,
                'result' => $result,
                'requested_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role processed successfully',
                'data' => $result,
            ], 201)->header('X-Request-ID', (string) Str::uuid());

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'ROLES_002',
            ], 422)->header('X-Request-ID', (string) Str::uuid());

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'ROLES_003',
            ], 400)->header('X-Request-ID', (string) Str::uuid());

        } catch (\Exception $e) {
            Log::error('Error adding single role', [
                'user_id' => $userId,
                'role_name' => $request->role_name ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while adding role',
                'error_code' => 'ROLES_004',
            ], 500)->header('X-Request-ID', (string) Str::uuid());
        }
    }

    /**
     * Get user's roles.
     */
    public function getUserRoles(Request $request, int $userId): JsonResponse
    {
        try {
            $request->validate([
                'page' => 'integer|min:1',
                'limit' => 'integer|min:1|max:100',
                'sort' => 'string|in:role_name,assigned_at',
            ]);

            $user = User::findOrFail($userId);
            
            // Check if user can manage roles for this user
            if (!$this->userRolesService->canManageUserRoles($request->user(), $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view roles for this user',
                    'error_code' => 'ROLES_001',
                ], 403)->header('X-Request-ID', (string) Str::uuid());
            }

            $page = (int) $request->get('page', 1);
            $limit = (int) $request->get('limit', 20);
            $sort = $request->get('sort', 'role_name');

            $result = $this->userRolesService->getUserRoles($user, $page, $limit, $sort);

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
            Log::error('Error getting user roles', [
                'user_id' => $userId,
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
     * Remove a role from a user.
     */
    public function removeRole(Request $request, int $userId, int $roleId): JsonResponse
    {
        try {
            $user = User::findOrFail($userId);
            
            // Check if user can manage roles for this user
            if (!$this->userRolesService->canManageUserRoles($request->user(), $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to manage roles for this user',
                    'error_code' => 'ROLES_001',
                ], 403)->header('X-Request-ID', (string) Str::uuid());
            }

            $removed = $this->userRolesService->removeRoleFromUser($user, $roleId, $request);

            if (!$removed) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role not found for this user',
                    'error_code' => 'ROLES_005',
                ], 404)->header('X-Request-ID', (string) Str::uuid());
            }

            // Log the action
            Log::info('Role removed from user', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'requested_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role removed successfully',
            ], 204)->header('X-Request-ID', (string) Str::uuid());

        } catch (\Exception $e) {
            Log::error('Error removing role from user', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing role',
                'error_code' => 'ROLES_004',
            ], 500)->header('X-Request-ID', (string) Str::uuid());
        }
    }

    /**
     * Reorder user's roles.
     */
    public function reorderRoles(Request $request, int $userId): JsonResponse
    {
        try {
            $request->validate([
                'role_orders' => 'required|array|min:1',
                'role_orders.*.role_id' => 'required|integer',
                'role_orders.*.display_order' => 'required|integer|min:1',
            ]);

            $user = User::findOrFail($userId);
            
            // Check if user can manage roles for this user
            if (!$this->userRolesService->canManageUserRoles($request->user(), $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to manage roles for this user',
                    'error_code' => 'ROLES_001',
                ], 403)->header('X-Request-ID', (string) Str::uuid());
            }

            $result = $this->userRolesService->reorderUserRoles($user, $request->role_orders, $request);

            // Log the action
            Log::info('User roles reordered', [
                'user_id' => $userId,
                'role_orders' => $request->role_orders,
                'updated_count' => $result['updated_count'],
                'requested_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Roles reordered successfully',
                'data' => $result,
            ], 200)->header('X-Request-ID', (string) Str::uuid());

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'ROLES_002',
            ], 422)->header('X-Request-ID', (string) Str::uuid());

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'ROLES_003',
            ], 400)->header('X-Request-ID', (string) Str::uuid());

        } catch (\Exception $e) {
            Log::error('Error reordering user roles', [
                'user_id' => $userId,
                'role_orders' => $request->role_orders ?? [],
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while reordering roles',
                'error_code' => 'ROLES_004',
            ], 500)->header('X-Request-ID', (string) Str::uuid());
        }
    }

    /**
     * Set display order for a single role.
     */
    public function setRoleOrder(Request $request, int $userId, int $roleId): JsonResponse
    {
        try {
            $request->validate([
                'display_order' => 'required|integer|min:1',
            ]);

            $user = User::findOrFail($userId);
            
            // Check if user can manage roles for this user
            if (!$this->userRolesService->canManageUserRoles($request->user(), $userId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to manage roles for this user',
                    'error_code' => 'ROLES_001',
                ], 403)->header('X-Request-ID', (string) Str::uuid());
            }

            $this->userRolesService->setRoleOrder($user, $roleId, $request->display_order, $request);

            // Log the action
            Log::info('User role order set', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'display_order' => $request->display_order,
                'requested_by' => $request->user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Role order updated successfully',
            ], 200)->header('X-Request-ID', (string) Str::uuid());

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'ROLES_002',
            ], 422)->header('X-Request-ID', (string) Str::uuid());

        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'ROLES_003',
            ], 400)->header('X-Request-ID', (string) Str::uuid());

        } catch (\Exception $e) {
            Log::error('Error setting role order', [
                'user_id' => $userId,
                'role_id' => $roleId,
                'display_order' => $request->display_order ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while setting role order',
                'error_code' => 'ROLES_004',
            ], 500)->header('X-Request-ID', (string) Str::uuid());
        }
    }
}
