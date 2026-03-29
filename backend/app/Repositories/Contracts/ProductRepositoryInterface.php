<?php

namespace App\Repositories\Contracts;

interface ProductRepositoryInterface
{
  public function paginate(array $filters, int $page, int $limit): array;

  public function findById(string $id): ?\App\Models\Product;

  public function findBySlug(string $slug): ?\App\Models\Product;

  public function getFeatured(int $limit): \Illuminate\Database\Eloquent\Collection;

  public function create(array $data): \App\Models\Product;

  public function update(string $id, array $data): \App\Models\Product;

  public function delete(string $id): void;
}
