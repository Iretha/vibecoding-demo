<?php

declare(strict_types=1);

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\LogoutResponse;
use Laravel\Fortify\Contracts\PasswordResetResponse;
use Laravel\Fortify\Contracts\RegisterResponse;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Custom login response
        $this->app->instance(LoginResponse::class, new class implements LoginResponse
        {
            public function toResponse($request)
            {
                $user = $request->user();
                $remember = $request->boolean('remember', false);
                $ttl = $remember ? 60 * 24 * 30 : 60; // 30 days or 1 hour
                
                $token = $user->createToken('auth-token', ['*'], now()->addMinutes($ttl))->plainTextToken;

                return response()->json([
                    'success' => true,
                    'message' => 'Login successful',
                    'data' => [
                        'user' => $user,
                        'token' => $token,
                        'expires_at' => now()->addMinutes($ttl)->toIso8601String(),
                    ],
                ], 200)->header('X-Request-ID', (string) Str::uuid());
            }
        });

        // Custom register response
        $this->app->instance(RegisterResponse::class, new class implements RegisterResponse
        {
            public function toResponse($request)
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Registration successful. Please check your email for verification.',
                    'data' => [
                        'user' => $request->user() ?? User::where('email', $request->email)->first(),
                    ],
                ], 201)->header('X-Request-ID', (string) Str::uuid());
            }
        });

        // Custom logout response
        $this->app->instance(LogoutResponse::class, new class implements LogoutResponse
        {
            public function toResponse($request)
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Logout successful',
                ], 200)->header('X-Request-ID', (string) Str::uuid());
            }
        });

        // Custom password reset response
        $this->app->instance(PasswordResetResponse::class, new class implements PasswordResetResponse
        {
            public function toResponse($request)
            {
                return response()->json([
                    'success' => true,
                    'message' => 'Password has been reset successfully',
                ], 200)->header('X-Request-ID', (string) Str::uuid());
            }
        });
    }

    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);

        // Configure email verification URL
        Fortify::verifyEmailView(function () {
            return redirect(config('app.url') . '/api/v1/auth/email/verify');
        });

        // Configure email verification URL
        Fortify::verifyEmailView(function () {
            return redirect(config('app.url') . '/api/v1/auth/email/verify');
        });

        // Custom authentication logic
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', $request->email)->first();

            if (! $user) {
                return null;
            }

            // Check if account is locked
            if ($user->isLocked()) {
                return null;
            }

            // Check if email is verified
            if (! $user->hasVerifiedEmail()) {
                return null;
            }

            if (Hash::check($request->password, $user->password)) {
                return $user;
            }

            // Increment failed attempts for wrong password
            $user->incrementFailedAttempts();

            return null;
        });

        // Rate limiters
        RateLimiter::for('login', function (Request $request) {
            $email = (string) $request->email;
            $key = 'login-email:' . Str::lower($email);
            
            return Limit::perMinutes(15, 5)->by($key)->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many login attempts. Please try again later.',
                    'error_code' => 'AUTH_004',
                ], 429)->header('X-Request-ID', (string) Str::uuid());
            });
        });

        RateLimiter::for('login-ip', function (Request $request) {
            return Limit::perMinutes(15, 20)->by($request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.',
                    'error_code' => 'AUTH_004',
                ], 429)->header('X-Request-ID', (string) Str::uuid());
            });
        });

        RateLimiter::for('forgot-password', function (Request $request) {
            return Limit::perMinutes(15, 10)->by($request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.',
                    'error_code' => 'AUTH_004',
                ], 429)->header('X-Request-ID', (string) Str::uuid());
            });
        });

        RateLimiter::for('token-refresh', function (Request $request) {
            return Limit::perHour(10)->by($request->user()?->id ?: $request->ip())->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many refresh requests. Please try again later.',
                    'error_code' => 'AUTH_004',
                ], 429)->header('X-Request-ID', (string) Str::uuid());
            });
        });
    }
}



