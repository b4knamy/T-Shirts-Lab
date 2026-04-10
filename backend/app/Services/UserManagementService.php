<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
  public function paginate(array $filters, int $perPage): LengthAwarePaginator
  {
    $query = User::query()->latest();

    if (! empty($filters['role'])) {
      $query->where('role', $filters['role']);
    }

    if (! empty($filters['search'])) {
      $search = $filters['search'];
      $query->where(function ($q) use ($search) {
        $q->where('first_name', 'ilike', "%{$search}%")
          ->orWhere('last_name', 'ilike', "%{$search}%")
          ->orWhere('email', 'ilike', "%{$search}%");
      });
    }

    return $query->paginate($perPage);
  }

  /**
   * @return User|string  Returns the created user on success, or an error string.
   */
  public function createStaff(User $currentUser, array $data): User|string
  {
    if ($data['role'] === 'ADMIN' && $currentUser->role !== 'SUPER_ADMIN') {
      return 'Only Super Admins can create Admin users';
    }

    return User::create([
      'email' => $data['email'],
      'password_hash' => Hash::make($data['password']),
      'first_name' => $data['first_name'],
      'last_name' => $data['last_name'],
      'phone' => $data['phone'] ?? null,
      'role' => $data['role'],
      'is_active' => true,
    ]);
  }

  /**
   * @return User|string|null  Returns the updated user on success, an error string on authorization failure, null if not found.
   */
  public function update(User $currentUser, string $targetId, array $data): User|string|null
  {
    $targetUser = User::findOrFail($targetId);

    if ($currentUser->id === $targetUser->id) {
      return 'You cannot modify your own account here';
    }

    if ($targetUser->role === 'SUPER_ADMIN') {
      return 'Cannot modify Super Admin accounts';
    }

    if ($targetUser->role === 'ADMIN' && $currentUser->role !== 'SUPER_ADMIN') {
      return 'Only Super Admins can modify Admin users';
    }

    if (isset($data['role']) && $data['role'] === 'ADMIN' && $currentUser->role !== 'SUPER_ADMIN') {
      return 'Only Super Admins can promote users to Admin';
    }

    $targetUser->update($data);

    return $targetUser->fresh();
  }
}
