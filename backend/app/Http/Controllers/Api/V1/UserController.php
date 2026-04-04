<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    use ApiResponse;

    public function me(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        return $this->success(new UserResource($user));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $user->update($request->validated());
        $user->refresh();

        return $this->success(new UserResource($user), 'Profile updated');
    }
}
