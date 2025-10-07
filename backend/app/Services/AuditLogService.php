<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * Log a role-related action.
     */
    public function logRoleAction(
        User $user,
        string $event,
        array $metadata = [],
        ?Request $request = null
    ): void {
        $auditData = [
            'event' => $event,
            'user_id' => $user->id,
            'ip_address' => $request?->ip() ?? '127.0.0.1',
            'user_agent' => $request?->userAgent(),
            'metadata' => $metadata,
            'created_at' => now(),
        ];

        AuditLog::create($auditData);
    }

    /**
     * Log role assignment.
     */
    public function logRoleAssignment(
        User $user,
        array $addedRoles,
        array $skippedRoles = [],
        ?Request $request = null
    ): void {
        $this->logRoleAction(
            $user,
            'roles_assigned',
            [
                'added_roles' => $addedRoles,
                'skipped_roles' => $skippedRoles,
                'total_added' => count($addedRoles),
                'total_skipped' => count($skippedRoles),
            ],
            $request
        );
    }

    /**
     * Log role removal.
     */
    public function logRoleRemoval(
        User $user,
        int $roleId,
        string $roleName,
        ?Request $request = null
    ): void {
        $this->logRoleAction(
            $user,
            'role_removed',
            [
                'role_id' => $roleId,
                'role_name' => $roleName,
            ],
            $request
        );
    }
}
