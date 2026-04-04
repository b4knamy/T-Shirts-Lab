<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class UserManagementController extends Controller
{
  use ApiResponse;

  /**
   * List all users (admin only).
   */
  public function index(Request $request): JsonResponse
  {
    $query = User::query()->latest();

    if ($request->filled('role')) {
      $query->where('role', $request->input('role'));
    }

    if ($request->filled('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('first_name', 'ilike', "%{$search}%")
          ->orWhere('last_name', 'ilike', "%{$search}%")
          ->orWhere('email', 'ilike', "%{$search}%");
      });
    }

    $users = $query->paginate($request->input('limit', 15));

    return $this->success([
      'data' => UserResource::collection($users),
      'meta' => [
        'total'       => $users->total(),
        'page'        => $users->currentPage(),
        'limit'       => $users->perPage(),
        'total_pages' => $users->lastPage(),
      ],
    ]);
  }

  /**
   * Create a staff user (MODERATOR).
   * Only ADMIN and SUPER_ADMIN can create moderators.
   * Only SUPER_ADMIN can create ADMIN users.
   */
  public function store(Request $request): JsonResponse
  {
    $currentUser = JWTAuth::parseToken()->authenticate();

    $data = $request->validate([
      'email'      => 'required|email|unique:users,email',
      'password'   => 'required|string|min:8',
      'first_name' => 'required|string|max:100',
      'last_name'  => 'required|string|max:100',
      'phone'      => 'nullable|string|max:20',
      'role'       => 'required|in:MODERATOR,ADMIN',
    ]);

    // Only SUPER_ADMIN can create ADMIN users
    if ($data['role'] === 'ADMIN' && $currentUser->role !== 'SUPER_ADMIN') {
      return $this->error('Only Super Admins can create Admin users', 403);
    }

    $user = User::create([
      'email'         => $data['email'],
      'password_hash' => Hash::make($data['password']),
      'first_name'    => $data['first_name'],
      'last_name'     => $data['last_name'],
      'phone'         => $data['phone'] ?? null,
      'role'          => $data['role'],
      'is_active'     => true,
    ]);

    return $this->success(new UserResource($user), 'Staff member created', 201);
  }

  /**
   * Update a user's role or active status.
   */
  public function update(Request $request, string $id): JsonResponse
  {
    $currentUser = JWTAuth::parseToken()->authenticate();
    $targetUser  = User::findOrFail($id);

    // Can't modify yourself
    if ($currentUser->id === $targetUser->id) {
      return $this->error('You cannot modify your own account here', 422);
    }

    // Can't modify SUPER_ADMIN
    if ($targetUser->role === 'SUPER_ADMIN') {
      return $this->error('Cannot modify Super Admin accounts', 403);
    }

    // Only SUPER_ADMIN can change ADMIN users
    if ($targetUser->role === 'ADMIN' && $currentUser->role !== 'SUPER_ADMIN') {
      return $this->error('Only Super Admins can modify Admin users', 403);
    }

    $data = $request->validate([
      'role'      => 'sometimes|in:CUSTOMER,MODERATOR,ADMIN',
      'is_active' => 'sometimes|boolean',
    ]);

    // Only SUPER_ADMIN can promote to ADMIN
    if (isset($data['role']) && $data['role'] === 'ADMIN' && $currentUser->role !== 'SUPER_ADMIN') {
      return $this->error('Only Super Admins can promote users to Admin', 403);
    }

    $targetUser->update($data);

    return $this->success(new UserResource($targetUser->fresh()), 'User updated');
  }
}
