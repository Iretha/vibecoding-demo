<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccountLocked;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

class AccountLockListener
{
    public function handle(AccountLocked $event): void
    {
        $user = $event->user;

        // Log account locked event
        Queue::push(function () use ($user) {
            AuditLog::create([
                'event' => 'account_locked',
                'user_id' => $user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [
                    'email' => $user->email,
                    'locked_until' => $user->locked_until->toIso8601String(),
                ],
            ]);
        });

        // Send email notification if configured
        if (config('audit.notify_on.account_locked')) {
            $minutes = now()->diffInMinutes($user->locked_until);
            
            Mail::raw(
                "Your account has been locked due to multiple failed login attempts. Please try again in {$minutes} minutes.",
                function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Account Locked - ' . config('app.name'));
                }
            );
        }
    }
}



