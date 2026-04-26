<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\ChangePasswordRequest;
use App\Http\Requests\Api\V1\User\DeleteAccountRequest;
use App\Http\Requests\Api\V1\User\StoreAddressRequest;
use App\Http\Requests\Api\V1\User\UpdateAddressRequest;
use App\Http\Requests\Api\V1\User\UpdateProfileRequest;
use App\Http\Requests\Api\V1\User\UploadAvatarRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\UserService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserService $userService,
        private readonly AuthService $authService,
    ) {}

    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return $this->success(new UserResource($this->userService->getProfile($user)));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $updated = $this->userService->updateProfile($user, $request->validated());

        return $this->success(new UserResource($updated), 'Profile updated');
    }

    /* ── Upload avatar ──────────────────────────────────────────────── */
    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $updated = $this->userService->uploadAvatar($user, $request->file('avatar'));

        return $this->success(new UserResource($updated), 'Avatar uploaded');
    }

    /* ── Change password ─────────────────────────────────────────────── */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        try {
            $this->authService->changePassword(
                user: $user,
                currentPassword: $request->validated('current_password'),
                newPassword: $request->validated('password'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(null, 'Password changed successfully. Please log in again.');
    }

    public function destroy(DeleteAccountRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        try {
            $this->authService->deleteAccount(
                user: $user,
                currentPassword: $request->validated('current_password'),
            );
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(null, 'Account deleted successfully.');
    }

    /* ── Address CRUD ───────────────────────────────────────────────── */
    public function addresses(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        return $this->success($this->userService->getAddresses($user));
    }

    public function storeAddress(StoreAddressRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $address = $this->userService->addAddress($user, $request->validated());

        return $this->success($address, 'Address added', 201);
    }

    public function updateAddress(UpdateAddressRequest $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $address = $this->userService->updateAddress($user, $id, $request->validated());

        if (! $address) {
            return $this->error('Address not found', 404);
        }

        return $this->success($address, 'Address updated');
    }

    public function destroyAddress(string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $address = $this->userService->deleteAddress($user, $id);

        if (! $address) {
            return $this->error('Address not found', 404);
        }

        return $this->success(null, 'Address deleted');
    }
}
