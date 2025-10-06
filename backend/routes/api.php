<?php

declare(strict_types=1);

use App\Http\Middleware\CheckAccountLocked;
use App\Http\Middleware\EnsureEmailIsVerified;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

// Health check endpoint (public, no rate limiting)
Route::get('/health', function () {
    try {
        // Check database
        DB::connection()->getPdo();
        $dbStatus = 'connected';
    } catch (\Exception $e) {
        $dbStatus = 'disconnected';
    }

    try {
        // Check Redis
        Redis::connection()->ping();
        $redisStatus = 'connected';
    } catch (\Exception $e) {
        $redisStatus = 'disconnected';
    }

    $isHealthy = $dbStatus === 'connected' && $redisStatus === 'connected';

    return response()->json([
        'success' => $isHealthy,
        'data' => [
            'status' => $isHealthy ? 'healthy' : 'degraded',
            'services' => [
                'database' => $dbStatus,
                'redis' => $redisStatus,
            ],
        ],
    ], $isHealthy ? 200 : 503)->header('X-Request-ID', (string) Str::uuid());
})->withoutMiddleware(['throttle:api']);

// Auth routes under /api/v1/auth prefix
Route::prefix('v1/auth')->group(function () {
// Registration
Route::post('/register', [\Laravel\Fortify\Http\Controllers\RegisteredUserController::class, 'store'])
    ->middleware(['throttle:forgot-password']);

// Login
Route::post('/login', function (Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $user = Auth::user();
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

    return response()->json([
        'success' => false,
        'message' => 'Invalid credentials',
        'error_code' => 'AUTH_001',
    ], 401)->header('X-Request-ID', (string) Str::uuid());
})->middleware(['throttle:login', 'throttle:login-ip']);

    // Logout (idempotent - always returns success)
    Route::post('/logout', function (Request $request) {
        try {
            $request->user()?->currentAccessToken()?->delete();
        } catch (\Exception $e) {
            // Silently handle any errors
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout successful',
        ], 200)->header('X-Request-ID', (string) Str::uuid());
    });

    // Forgot password
    Route::post('/forgot-password', function (Request $request) {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'We could not find a user with that email address.',
                'error_code' => 'AUTH_001',
            ], 422)->header('X-Request-ID', (string) Str::uuid());
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email first.',
                'error_code' => 'AUTH_003',
            ], 422)->header('X-Request-ID', (string) Str::uuid());
        }

        $status = Password::sendResetLink($request->only('email'));

        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent to your email.',
        ], 200)->header('X-Request-ID', (string) Str::uuid());
    })->middleware(['throttle:forgot-password']);

    // Reset password
    Route::post('/reset-password', function (Request $request) {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                app(\App\Actions\Fortify\ResetUserPassword::class)->reset($user, ['password' => $password, 'password_confirmation' => $password]);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'success' => true,
                'message' => 'Password has been reset successfully',
            ], 200)->header('X-Request-ID', (string) Str::uuid());
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid or expired token.',
            'error_code' => 'AUTH_006',
        ], 422)->header('X-Request-ID', (string) Str::uuid());
    });

    // Email verification - GET (redirect to frontend)
    Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return redirect(env('FRONTEND_URL', 'http://localhost:8200') . '/email-verified?status=error&code=AUTH_007');
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(env('FRONTEND_URL', 'http://localhost:8200') . '/email-verified?status=already_verified');
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        return redirect(env('FRONTEND_URL', 'http://localhost:8200') . '/email-verified?status=success');
    })->middleware(['signed', 'throttle:6,1'])->name('verification.verify');

    // Resend verification email
    Route::post('/email/resend', function (Request $request) {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified.',
            ], 400)->header('X-Request-ID', (string) Str::uuid());
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json([
            'success' => true,
            'message' => 'Verification email sent.',
        ], 200)->header('X-Request-ID', (string) Str::uuid());
    })->middleware(['auth:sanctum', 'throttle:forgot-password']);

    // Token refresh
    Route::post('/refresh', function (Request $request) {
        $user = $request->user();
        $currentToken = $user->currentAccessToken();
        
        // Determine TTL based on original token (approximation)
        $remember = false; // Default to standard TTL
        $ttl = $remember ? 60 * 24 * 30 : 60;

        // Create new token
        $newToken = $user->createToken('auth-token', ['*'], now()->addMinutes($ttl))->plainTextToken;

        // Revoke old token
        $currentToken->delete();

        return response()->json([
            'success' => true,
            'message' => 'Token refreshed successfully',
            'data' => [
                'token' => $newToken,
                'expires_at' => now()->addMinutes($ttl)->toIso8601String(),
            ],
        ], 200)->header('X-Request-ID', (string) Str::uuid());
    })->middleware(['auth:sanctum', 'throttle:token-refresh']);
});

// Protected route - Get current user
Route::get('/v1/user', function (Request $request) {
    return response()->json([
        'success' => true,
        'data' => $request->user(),
    ], 200)->header('X-Request-ID', (string) Str::uuid());
})->middleware(['auth:sanctum', CheckAccountLocked::class, EnsureEmailIsVerified::class]);


