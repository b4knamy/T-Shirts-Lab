<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CategoryService
{
    public function paginate(array $filters, int $page, int $limit): array
    {
        $query = Category::orderBy('name');

        if (! empty($filters['search'])) {
            $query->where('name', 'ilike', '%' . $filters['search'] . '%');
        }

        if (! empty($filters['status'])) {
            $query->where('is_active', $filters['status'] === 'active');
        }

        $total = $query->count();
        $categories = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return compact('categories', 'total');
    }

    public function create(array $data): Category
    {
        $data['slug'] = Str::slug($data['name']);

        if (Category::where('slug', $data['slug'])->exists()) {
            $data['slug'] .= '-' . Str::random(4);
        }

        $data['is_active'] = $data['is_active'] ?? true;

        $category = Category::create($data);
        Cache::forget('categories:all');

        return $category;
    }

    public function update(string $id, array $data): ?Category
    {
        $category = Category::find($id);

        if (! $category) {
            return null;
        }

        if (isset($data['name'])) {
            $slug = Str::slug($data['name']);
            if ($slug !== $category->slug && Category::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                $slug .= '-' . Str::random(4);
            }
            $data['slug'] = $slug;
        }

        $category->update($data);
        Cache::forget('categories:all');

        return $category->fresh();
    }

    /**
     * @return Category|string|null  Returns the Category on success, a string error message on failure, null if not found.
     */
    public function delete(string $id): Category|string|null
    {
        $category = Category::find($id);

        if (! $category) {
            return null;
        }

        if ($category->products()->exists()) {
            return 'Cannot delete category with existing products. Re-assign products first.';
        }

        $category->delete();
        Cache::forget('categories:all');

        return $category;
    }
}
