<?php

namespace App\Repositories\Contracts;

interface OrderRepositoryInterface
{
  public function findById(string $id): ?\App\Models\Order;

  public function paginateAll(int $page, int $limit): array;

  public function paginateForUser(string $userId, int $page, int $limit): array;

  public function create(array $data): \App\Models\Order;

  public function updateStatus(string $id, string $status, ?string $adminNotes = null): \App\Models\Order;
}
