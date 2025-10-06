<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccountLocked;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Queue;

class LoginAttemptListener
{
    public function handleSuccess(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            // Update login info and reset failed attempts
            $user->update([
                'last_login_at' => now(),
                'last_login_ip' => request()->ip(),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ]);

            // Log successful login
            Queue::push(function () use ($user) {
                AuditLog::create([
                    'event' => 'login_success',
                    'user_id' => $user->id,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'remember' => request()->boolean('remember', false),
                    ],
                ]);
            });
        }
    }

    public function handleFailed(Failed $event): void
    {
        if (! $event->user instanceof User) {
            // User not found, log generic failed attempt
            Queue::push(function () use ($event) {
                AuditLog::create([
                    'event' => 'login_failed',
                    'user_id' => null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'metadata' => [
                        'email' => $event->credentials['email'] ?? null,
                        'reason' => 'invalid_credentials',
                    ],
                ]);
            });
            return;
        }

        $user = $event->user;

        // Increment failed attempts
        $user->incrementFailedAttempts();

        // Log failed attempt
        Queue::push(function () use ($user) {
            AuditLog::create([
                'event' => 'login_failed',
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'email' => $user->email,
                    'reason' => 'invalid_credentials',
                ],
            ]);
        });

        // Check if account is now locked
        if ($user->isLocked()) {
            event(new AccountLocked($user));
        }
    }
}



