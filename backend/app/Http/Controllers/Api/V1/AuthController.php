<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->jsonResponse(
            [
                'user'         => new UserResource($result['user']),
                'accessToken'  => $result['accessToken'],
                'refreshToken' => $result['refreshToken'],
            ],
            'User registered successfully',
            201
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->validated('email'),
                $request->validated('password')
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorJsonResponse($e->getMessage(), $e->getCode() ?: 401);
        }

        return $this->jsonResponse([
            'user'         => new UserResource($result['user']),
            'accessToken'  => $result['accessToken'],
            'refreshToken' => $result['refreshToken'],
        ], 'Login successful');
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->refresh($request->validated('refreshToken'));
        } catch (InvalidArgumentException $e) {
            return $this->errorJsonResponse($e->getMessage(), 401);
        }

        return $this->jsonResponse([
            'accessToken'  => $result['accessToken'],
            'refreshToken' => $result['refreshToken'],
        ], 'Token refreshed');
    }

    public function logout()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $this->authService->logout($user);

        return response()->noContent();
    }
}
