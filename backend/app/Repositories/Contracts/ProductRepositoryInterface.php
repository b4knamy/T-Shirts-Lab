<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepositoryInterface
{
    public function paginate(array $filters, int $page, int $limit): array;

    public function findById(string $id): ?Product;

    public function findBySlug(string $slug): ?Product;

    public function getFeatured(int $limit): Collection;

    public function create(array $data): Product;

    public function update(string $id, array $data): Product;

    public function delete(string $id): void;
}
