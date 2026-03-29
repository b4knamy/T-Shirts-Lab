<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Symfony\Component\HttpFoundation\Response;

class JwtAuthenticate
{
  public function handle(Request $request, Closure $next): Response
  {
    try {
      $user = JWTAuth::parseToken()->authenticate();

      if (!$user) {
        return response()->json([
          'success' => false,
          'message' => 'User not found',
        ], 401);
      }

      if (!$user->is_active) {
        return response()->json([
          'success' => false,
          'message' => 'Account is disabled',
        ], 403);
      }
    } catch (TokenExpiredException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Token expired',
      ], 401);
    } catch (TokenInvalidException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Token invalid',
      ], 401);
    } catch (JWTException $e) {
      return response()->json([
        'success' => false,
        'message' => 'Token not provided',
      ], 401);
    }

    return $next($request);
  }
}
