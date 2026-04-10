<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UserService
{
    public function getProfile(User $user): User
    {
        $user->load('addresses');

        return $user;
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);
        $user->refresh();
        $user->load('addresses');

        return $user;
    }

    public function uploadAvatar(User $user, UploadedFile $file): User
    {
        // Delete old avatar if stored locally
        if ($user->profile_picture_url && str_contains($user->profile_picture_url, '/storage/avatars/')) {
            $oldPath = str_replace(url('storage') . '/', '', $user->profile_picture_url);
            Storage::disk('public')->delete($oldPath);
        }

        $path = $file->store('avatars/' . $user->id, 'public');

        $user->update(['profile_picture_url' => url('storage/' . $path)]);
        $user->refresh();
        $user->load('addresses');

        return $user;
    }

    public function getAddresses(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return $user->addresses()->orderByDesc('is_default')->get();
    }

    public function addAddress(User $user, array $data): UserAddress
    {
        if (! empty($data['is_default'])) {
            $user->addresses()->update(['is_default' => false]);
        }

        if ($user->addresses()->count() === 0) {
            $data['is_default'] = true;
        }

        $data['user_id'] = $user->id;

        return UserAddress::create($data);
    }

    public function updateAddress(User $user, string $addressId, array $data): ?UserAddress
    {
        $address = $user->addresses()->find($addressId);

        if (! $address) {
            return null;
        }

        if (! empty($data['is_default'])) {
            $user->addresses()->where('id', '!=', $addressId)->update(['is_default' => false]);
        }

        $address->update($data);

        return $address->fresh();
    }

    public function deleteAddress(User $user, string $addressId): ?UserAddress
    {
        $address = $user->addresses()->find($addressId);

        if (! $address) {
            return null;
        }

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $next = $user->addresses()->first();
            $next?->update(['is_default' => true]);
        }

        return $address;
    }
}
