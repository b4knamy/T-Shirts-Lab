<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use App\Http\Resources\Api\V1\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class CategoryController extends Controller
{
  use ApiResponse;

  /* ── Admin: paginated list ──────────────────────────────────────── */
  public function index(Request $request): JsonResponse
  {
    $page  = (int) $request->get('page', 1);
    $limit = min((int) $request->get('limit', 50), 100);

    $query = Category::orderBy('name');

    if ($request->has('search')) {
      $query->where('name', 'ilike', '%' . $request->get('search') . '%');
    }

    $total      = $query->count();
    $categories = $query->skip(($page - 1) * $limit)->take($limit)->get();

    return $this->paginated(CategoryResource::collection($categories), $total, $page, $limit);
  }

  public function store(Request $request): JsonResponse
  {
    $data = $request->validate([
      'name'        => 'required|string|max:100',
      'description' => 'nullable|string|max:500',
      'image_url'   => 'nullable|string|url|max:500',
      'is_active'   => 'boolean',
    ]);

    $data['slug'] = Str::slug($data['name']);

    // Check for slug collision
    if (Category::where('slug', $data['slug'])->exists()) {
      $data['slug'] .= '-' . Str::random(4);
    }

    $data['is_active'] = $data['is_active'] ?? true;

    $category = Category::create($data);
    Cache::forget('categories:all');

    return $this->success(new CategoryResource($category), 'Category created', 201);
  }

  public function update(Request $request, string $id): JsonResponse
  {
    $category = Category::find($id);

    if (!$category) {
      return $this->error('Category not found', 404);
    }

    $data = $request->validate([
      'name'        => 'sometimes|string|max:100',
      'description' => 'nullable|string|max:500',
      'image_url'   => 'nullable|string|url|max:500',
      'is_active'   => 'boolean',
    ]);

    if (isset($data['name'])) {
      $slug = Str::slug($data['name']);
      if ($slug !== $category->slug && Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
        $slug .= '-' . Str::random(4);
      }
      $data['slug'] = $slug;
    }

    $category->update($data);
    Cache::forget('categories:all');

    return $this->success(new CategoryResource($category->fresh()), 'Category updated');
  }

  public function destroy(string $id): JsonResponse
  {
    $category = Category::find($id);

    if (!$category) {
      return $this->error('Category not found', 404);
    }

    // Don't allow deletion if products use this category
    if ($category->products()->exists()) {
      return $this->error('Cannot delete category with existing products. Re-assign products first.', 422);
    }

    $category->delete();
    Cache::forget('categories:all');

    return $this->success(null, 'Category deleted');
  }
}
