<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = JWTAuth::parseToken()->authenticate();

        if (! in_array($user->role, ['ADMIN', 'SUPER_ADMIN', 'MODERATOR'])) {
            return response()->json([
                'success' => false,
                'message' => 'Forbidden: Admin access required',
            ], 403);
        }

        return $next($request);
    }
}
