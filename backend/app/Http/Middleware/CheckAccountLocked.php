<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAccountLocked
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isLocked()) {
            $minutes = now()->diffInMinutes($user->locked_until);
            
            return response()->json([
                'success' => false,
                'message' => "Your account has been locked due to multiple failed login attempts. Please try again in {$minutes} minutes.",
                'error_code' => 'AUTH_002',
            ], 403);
        }

        return $next($request);
    }
}




