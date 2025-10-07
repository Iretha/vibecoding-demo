<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PasswordResetCompleted;
use App\Models\AuditLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Password\Events\PasswordResetLinkSent;
use Illuminate\Support\Facades\Queue;

class PasswordResetListener
{
    public function handleRequested(PasswordResetLinkSent $event): void
    {
        Queue::push(function () use ($event) {
            AuditLog::create([
                'event' => 'password_reset_requested',
                'user_id' => $event->user->id ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'email' => $event->user->email ?? null,
                ],
            ]);
        });
    }

    public function handleCompleted(PasswordResetCompleted $event): void
    {
        Queue::push(function () use ($event) {
            AuditLog::create([
                'event' => 'password_reset_completed',
                'user_id' => $event->user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [],
            ]);
        });
    }
}




