<?php

namespace App\Repositories\Eloquent;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductRepository implements ProductRepositoryInterface
{
  public function paginate(array $filters, int $page, int $limit): array
  {
    $query = Product::with(['category', 'images', 'designs']);

    if (!empty($filters['search'])) {
      $search = $filters['search'];
      $query->where(function ($q) use ($search) {
        $q->where('name', 'ilike', "%{$search}%")
          ->orWhere('description', 'ilike', "%{$search}%")
          ->orWhere('sku', 'ilike', "%{$search}%");
      });
    }

    if (!empty($filters['categoryId'])) {
      $query->where('category_id', $filters['categoryId']);
    }

    if (!empty($filters['status'])) {
      $query->where('status', $filters['status']);
    }

    if (!empty($filters['minPrice'])) {
      $query->where('price', '>=', $filters['minPrice']);
    }

    if (!empty($filters['maxPrice'])) {
      $query->where('price', '<=', $filters['maxPrice']);
    }

    $sortMap = [
      'price_asc'  => ['price', 'asc'],
      'price_desc' => ['price', 'desc'],
      'name_asc'   => ['name', 'asc'],
      'name_desc'  => ['name', 'desc'],
      'newest'     => ['created_at', 'desc'],
      'oldest'     => ['created_at', 'asc'],
    ];

    $sortBy = $filters['sortBy'] ?? 'newest';
    [$column, $direction] = $sortMap[$sortBy] ?? ['created_at', 'desc'];
    $query->orderBy($column, $direction);

    $total    = $query->count();
    $products = $query->skip(($page - 1) * $limit)->take($limit)->get();

    return compact('products', 'total');
  }

  public function findById(string $id): ?Product
  {
    return Product::with(['category', 'images', 'designs'])->find($id);
  }

  public function findBySlug(string $slug): ?Product
  {
    return Product::with(['category', 'images', 'designs'])
      ->where('slug', $slug)
      ->first();
  }

  public function getFeatured(int $limit): Collection
  {
    return Cache::remember("products:featured:{$limit}", 3600, function () use ($limit) {
      return Product::with(['category', 'images', 'designs'])
        ->where('is_featured', true)
        ->where('status', 'ACTIVE')
        ->limit($limit)
        ->get();
    });
  }

  public function create(array $data): Product
  {
    $product = Product::create($data);
    $this->clearCache();
    return $product->load(['category', 'images', 'designs']);
  }

  public function update(string $id, array $data): Product
  {
    $product = Product::findOrFail($id);
    $product->update($data);
    $this->clearCache();
    return $product->load(['category', 'images', 'designs']);
  }

  public function delete(string $id): void
  {
    $product = Product::findOrFail($id);
    $product->delete();
    $this->clearCache();
  }

  private function clearCache(): void
  {
    foreach ([4, 6, 8, 12] as $limit) {
      Cache::forget("products:featured:{$limit}");
    }
  }
}
