<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UserManagement\StoreStaffRequest;
use App\Http\Requests\Api\V1\UserManagement\UpdateUserRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\UserManagementService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserManagementController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserManagementService $userManagementService
    ) {}

    /**
     * List all users (admin only).
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['role', 'search']);
        $users = $this->userManagementService->paginate($filters, $request->input('limit', 15));

        return $this->success([
            'data' => UserResource::collection($users),
            'meta' => [
                'total' => $users->total(),
                'page' => $users->currentPage(),
                'limit' => $users->perPage(),
                'total_pages' => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Create a staff user (MODERATOR).
     * Only ADMIN and SUPER_ADMIN can create moderators.
     * Only SUPER_ADMIN can create ADMIN users.
     */
    public function store(StoreStaffRequest $request): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $data = $request->validated();

        $result = $this->userManagementService->createStaff($currentUser, $data);

        if (is_string($result)) {
            return $this->error($result, 403);
        }

        return $this->success(new UserResource($result), 'Staff member created', 201);
    }

    /**
     * Update a user's role or active status.
     */
    public function update(UpdateUserRequest $request, string $id): JsonResponse
    {
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $data = $request->validated();

        $result = $this->userManagementService->update($currentUser, $id, $data);

        if ($result === null) {
            return $this->error('User not found', 404);
        }

        if (is_string($result)) {
            // Only "You cannot modify your own account" is a client logic error (422).
            // All other authorization failures (Super Admin protection, role restrictions) are 403.
            $statusCode = stripos($result, 'you cannot modify') !== false ? 422 : 403;

            return $this->error($result, $statusCode);
        }

        return $this->success(new UserResource($result), 'User updated');
    }
}
