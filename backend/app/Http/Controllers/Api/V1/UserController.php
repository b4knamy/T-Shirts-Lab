<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\User\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\UserAddress;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    use ApiResponse;

    public function me(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $user->load('addresses');

        return $this->success(new UserResource($user));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $user->update($request->validated());
        $user->refresh();
        $user->load('addresses');

        return $this->success(new UserResource($user), 'Profile updated');
    }

    /* ── Upload avatar ──────────────────────────────────────────────── */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:3072',
        ]);

        $user = JWTAuth::parseToken()->authenticate();

        // Delete old avatar if it exists on local storage
        if ($user->profile_picture_url && str_contains($user->profile_picture_url, '/storage/avatars/')) {
            $oldPath = str_replace(url('storage') . '/', '', $user->profile_picture_url);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $request->file('avatar')->store('avatars/' . $user->id, 'public');

        $user->update(['profile_picture_url' => url('storage/' . $path)]);
        $user->refresh();
        $user->load('addresses');

        return $this->success(new UserResource($user), 'Avatar uploaded');
    }

    /* ── Address CRUD ───────────────────────────────────────────────── */
    public function addresses(): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        return $this->success($user->addresses()->orderByDesc('is_default')->get());
    }

    public function storeAddress(Request $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validate([
            'label'        => 'nullable|string|max:100',
            'street'       => 'required|string|max:255',
            'number'       => 'required|string|max:20',
            'complement'   => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'city'         => 'required|string|max:255',
            'state'        => 'required|string|max:2',
            'zip_code'     => 'required|string|max:20',
            'country'      => 'nullable|string|max:2',
            'is_default'   => 'boolean',
        ]);

        // If setting as default, unset others
        if (!empty($data['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        // If first address, make it default
        if ($user->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $data['user_id'] = $user->id;
        $address = UserAddress::create($data);

        return $this->success($address, 'Address added', 201);
    }

    public function updateAddress(Request $request, string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $address = $user->addresses()->find($id);

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        $data = $request->validate([
            'label'        => 'nullable|string|max:100',
            'street'       => 'sometimes|string|max:255',
            'number'       => 'sometimes|string|max:20',
            'complement'   => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:255',
            'city'         => 'sometimes|string|max:255',
            'state'        => 'sometimes|string|max:2',
            'zip_code'     => 'sometimes|string|max:20',
            'country'      => 'nullable|string|max:2',
            'is_default'   => 'boolean',
        ]);

        if (!empty($data['is_default'])) {
            $user->addresses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $address->update($data);

        return $this->success($address->fresh(), 'Address updated');
    }

    public function destroyAddress(string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $address = $user->addresses()->find($id);

        if (!$address) {
            return $this->error('Address not found', 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // Promote another address to default
        if ($wasDefault) {
            $next = $user->addresses()->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return $this->success(null, 'Address deleted');
    }
}
