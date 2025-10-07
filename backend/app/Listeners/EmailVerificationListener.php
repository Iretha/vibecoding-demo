<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\AuditLog;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Queue;

class EmailVerificationListener
{
    public function handleVerified(Verified $event): void
    {
        Queue::push(function () use ($event) {
            AuditLog::create([
                'event' => 'email_verified',
                'user_id' => $event->user->id,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => [],
            ]);
        });
    }
}




