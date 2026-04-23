<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AuthService;
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
                'user' => new UserResource($result['user']),
                'access_token' => $result['access_token'],
                'refresh_token' => $result['refresh_token'],
            ],
            'User registered successfully',
            201
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                email: $request->validated('email'),
                password: $request->validated('password')
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorJsonResponse($e->getMessage(), $e->getCode() ?: 401);
        }

        return $this->jsonResponse([
            'user' => new UserResource($result['user']),
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
        ], 'Login successful');
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->refresh($request->validated('refresh_token'));
        } catch (InvalidArgumentException $e) {
            return $this->errorJsonResponse($e->getMessage(), 401);
        }

        return $this->jsonResponse([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token'],
        ], 'Token refreshed');
    }

    public function logout()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $this->authService->logout($user);

        return response()->noContent();
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendPasswordResetLink($request->validated('email'));

        // Always return 200 regardless of whether the email exists (prevents enumeration)
        return $this->jsonResponse(null, 'If that email is registered you will receive a reset link shortly.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPassword(
                email: $request->validated('email'),
                token: $request->validated('token'),
                newPassword: $request->validated('password'),
            );
        } catch (InvalidArgumentException $e) {
            return $this->errorJsonResponse($e->getMessage(), 422);
        }

        return $this->jsonResponse(null, 'Password reset successfully. Please log in with your new password.');
    }
}
