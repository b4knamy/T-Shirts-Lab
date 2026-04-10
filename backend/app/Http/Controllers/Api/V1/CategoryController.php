<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Category\CategoryStoreRequest;
use App\Http\Requests\Api\V1\Category\CategoryUpdateRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Services\CategoryService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    /* ── Admin: paginated list ──────────────────────────────────────── */
    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->query('page', 1);
        $limit = min((int) $request->query('limit', 50), 100);

        $filters = $request->only(['search', 'status']);
        $result = $this->categoryService->paginate($filters, $page, $limit);

        return $this->paginated(
            CategoryResource::collection($result['categories']),
            $result['total'],
            $page,
            $limit
        );
    }

    public function store(CategoryStoreRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return $this->success(new CategoryResource($category), 'Category created', 201);
    }

    public function update(CategoryUpdateRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $category = $this->categoryService->update($id, $data);

        if (! $category) {
            return $this->error('Category not found', 404);
        }

        return $this->success(new CategoryResource($category), 'Category updated');
    }

    public function destroy(string $id): JsonResponse
    {
        $result = $this->categoryService->delete($id);

        if ($result === null) {
            return $this->error('Category not found', 404);
        }

        if (is_string($result)) {
            return $this->error($result, 422);
        }

        return $this->success(null, 'Category deleted');
    }
}
