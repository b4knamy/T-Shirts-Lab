<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductService
{
    public function paginate(array $filters, int $page, int $limit): array
    {
        $query = Product::with(['category', 'images', 'designs'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%")
                    ->orWhere('sku', 'ilike', "%{$search}%");
            });
        }

        if (! empty($filters['categoryId'])) {
            $query->where('category_id', $filters['categoryId']);
        }

        if (! empty($filters['status']) && $filters['status'] !== 'ALL') {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['minPrice'])) {
            $query->where('price', '>=', $filters['minPrice']);
        }

        if (! empty($filters['maxPrice'])) {
            $query->where('price', '<=', $filters['maxPrice']);
        }

        $sortMap = [
            'price_asc' => ['price', 'asc'],
            'price_desc' => ['price', 'desc'],
            'name_asc' => ['name', 'asc'],
            'name_desc' => ['name', 'desc'],
            'newest' => ['created_at', 'desc'],
            'oldest' => ['created_at', 'asc'],
        ];

        $sortBy = $filters['sortBy'] ?? 'newest';
        [$column, $direction] = $sortMap[$sortBy] ?? ['created_at', 'desc'];
        $query->orderBy($column, $direction);

        $total = $query->count();
        $products = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return compact('products', 'total');
    }

    public function findById(string $id): ?Product
    {
        return Product::with(['category', 'images', 'designs'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->find($id);
    }

    public function findBySlug(string $slug): ?Product
    {
        return Product::with(['category', 'images', 'designs'])
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->where('slug', $slug)
            ->first();
    }

    public function getFeatured(int $limit): Collection
    {
        return Cache::remember("products:featured:{$limit}", 3600, function () use ($limit) {
            return Product::with(['category', 'images', 'designs'])
                ->withAvg('reviews', 'rating')
                ->withCount('reviews')
                ->where('is_featured', true)
                ->where('status', 'ACTIVE')
                ->limit($limit)
                ->get();
        });
    }

    public function getCategories(): Collection
    {
        $rows = Cache::remember('categories:all', 86400, function () {
            return Category::where('is_active', true)->orderBy('name')->get()->toArray();
        });

        return Category::hydrate($rows);
    }

    public function create(array $data): Product
    {
        $product = Product::create([
            'name' => $data['name'],
            'slug' => Str::slug($data['name']).'-'.Str::random(6),
            'sku' => $data['sku'] ?? strtoupper('TSL-'.Str::random(8)),
            'description' => $data['description'],
            'long_description' => $data['long_description'] ?? null,
            'category_id' => $data['category_id'],
            'price' => $data['price'],
            'cost_price' => $data['cost_price'] ?? null,
            'discount_price' => $data['discount_price'] ?? null,
            'discount_percent' => $data['discount_percent'] ?? null,
            'stock_quantity' => $data['stock_quantity'] ?? 0,
            'status' => $data['status'] ?? 'ACTIVE',
            'is_featured' => $data['is_featured'] ?? false,
            'color' => $data['color'] ?? null,
            'size' => $data['size'] ?? null,
        ]);

        $this->clearFeaturedCache();

        return $product->load(['category', 'images', 'designs']);
    }

    public function update(string $id, array $data): Product
    {
        $product = Product::findOrFail($id);

        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = Str::slug($data['name']).'-'.Str::random(6);
        }

        foreach (
            [
                'description',
                'long_description',
                'category_id',
                'price',
                'cost_price',
                'discount_price',
                'discount_percent',
                'stock_quantity',
                'status',
                'is_featured',
                'color',
                'size',
            ] as $field
        ) {
            if (array_key_exists($field, $data)) {
                $updateData[$field] = $data[$field];
            }
        }

        $product->update($updateData);
        $this->clearFeaturedCache();

        return $product->load(['category', 'images', 'designs']);
    }

    public function delete(string $id): void
    {
        Product::findOrFail($id)->delete();
        $this->clearFeaturedCache();
    }

    private function clearFeaturedCache(): void
    {
        foreach ([4, 6, 8, 12] as $limit) {
            Cache::forget("products:featured:{$limit}");
        }
    }
}
