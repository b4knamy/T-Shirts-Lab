<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\JwtPasswordVersion;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureJwtPasswordVersionIsValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided',
            ], 401);
        }

        if (! JwtPasswordVersion::matches($request, $user)) {
            return response()->json([
                'success' => false,
                'message' => 'Token invalid',
            ], 401);
        }

        return $next($request);
    }
}