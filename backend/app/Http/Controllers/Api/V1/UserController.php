<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\StoreAddressRequest;
use App\Http\Requests\Api\V1\User\UpdateAddressRequest;
use App\Http\Requests\Api\V1\User\UpdateProfileRequest;
use App\Http\Requests\Api\V1\User\UploadAvatarRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserService $userService
    ) {}

    public function me(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        return $this->success(new UserResource($this->userService->getProfile($user)));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $updated = $this->userService->updateProfile($user, $request->validated());

        return $this->success(new UserResource($updated), 'Profile updated');
    }

    /* ── Upload avatar ──────────────────────────────────────────────── */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $updated = $this->userService->uploadAvatar($user, $request->file('avatar'));

        return $this->success(new UserResource($updated), 'Avatar uploaded');
    }

    /* ── Address CRUD ───────────────────────────────────────────────── */
    public function addresses(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        return $this->success($this->userService->getAddresses($user));
    }

    public function storeAddress(StoreAddressRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $address = $this->userService->addAddress($user, $request->validated());

        return $this->success($address, 'Address added', 201);
    }

    public function updateAddress(UpdateAddressRequest $request, string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $address = $this->userService->updateAddress($user, $id, $request->validated());

        if (! $address) {
            return $this->error('Address not found', 404);
        }

        return $this->success($address, 'Address updated');
    }

    public function destroyAddress(string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $address = $this->userService->deleteAddress($user, $id);

        if (! $address) {
            return $this->error('Address not found', 404);
        }

        return $this->success(null, 'Address deleted');
    }
}
