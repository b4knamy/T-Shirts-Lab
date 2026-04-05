<?php

namespace App\Repositories\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function findById(string $id): ?Order;

    public function paginateAll(int $page, int $limit): array;

    public function paginateForUser(string $userId, int $page, int $limit): array;

    public function create(array $data): Order;

    public function updateStatus(string $id, string $status, ?string $adminNotes = null): Order;
}
