<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\AccountLocked;
use App\Events\PasswordResetCompleted;
use App\Listeners\AccountLockListener;
use App\Listeners\EmailVerificationListener;
use App\Listeners\LoginAttemptListener;
use App\Listeners\PasswordResetListener;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Password\Events\PasswordResetLinkSent;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Login::class => [
            [LoginAttemptListener::class, 'handleSuccess'],
        ],
        Failed::class => [
            [LoginAttemptListener::class, 'handleFailed'],
        ],
        PasswordResetLinkSent::class => [
            [PasswordResetListener::class, 'handleRequested'],
        ],
        PasswordResetCompleted::class => [
            [PasswordResetListener::class, 'handleCompleted'],
        ],
        Verified::class => [
            [EmailVerificationListener::class, 'handleVerified'],
        ],
        AccountLocked::class => [
            AccountLockListener::class,
        ],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}




