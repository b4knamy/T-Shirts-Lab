<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function paginate(array $filters, int $page, int $limit): array
    {
        return $this->productRepository->paginate($filters, $page, $limit);
    }

    public function findById(string $id): Product
    {
        return $this->productRepository->findById($id);
    }

    public function findBySlug(string $slug): Product
    {
        return $this->productRepository->findBySlug($slug);
    }

    public function getFeatured(int $limit): Collection
    {
        return $this->productRepository->getFeatured($limit);
    }

    public function getCategories(): Collection
    {
        $rows = Cache::remember('categories:all', 86400, function () {
            return Category::where('is_active', true)->orderBy('name')->get()->toArray();
        });

        // Hydrate arrays back into Eloquent models so Resources can access
        // properties (->id, ->name, etc.) normally.
        return Category::hydrate($rows);
    }

    public function create(array $data): Product
    {
        return $this->productRepository->create([
            'name'             => $data['name'],
            'slug'             => Str::slug($data['name']) . '-' . Str::random(6),
            'sku'              => $data['sku'] ?? strtoupper('TSL-' . Str::random(8)),
            'description'      => $data['description'],
            'long_description' => $data['longDescription'] ?? null,
            'category_id'      => $data['categoryId'],
            'price'            => $data['price'],
            'cost_price'       => $data['costPrice'] ?? null,
            'discount_price'   => $data['discountPrice'] ?? null,
            'discount_percent' => $data['discountPercent'] ?? null,
            'stock_quantity'   => $data['stockQuantity'] ?? 0,
            'status'           => $data['status'] ?? 'ACTIVE',
            'is_featured'      => $data['isFeatured'] ?? false,
            'color'            => $data['color'] ?? null,
            'size'             => $data['size'] ?? null,
        ]);
    }

    public function update(string $id, array $data): Product
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
            $updateData['slug'] = Str::slug($data['name']) . '-' . Str::random(6);
        }

        $fieldMap = [
            'description'    => 'description',
            'longDescription' => 'long_description',
            'categoryId'     => 'category_id',
            'price'          => 'price',
            'costPrice'      => 'cost_price',
            'discountPrice'  => 'discount_price',
            'discountPercent' => 'discount_percent',
            'stockQuantity'  => 'stock_quantity',
            'status'         => 'status',
            'isFeatured'     => 'is_featured',
            'color'          => 'color',
            'size'           => 'size',
        ];

        foreach ($fieldMap as $inputKey => $dbColumn) {
            if (array_key_exists($inputKey, $data)) {
                $updateData[$dbColumn] = $data[$inputKey];
            }
        }

        return $this->productRepository->update($id, $updateData);
    }

    public function delete(string $id): void
    {
        $this->productRepository->delete($id);
    }
}
