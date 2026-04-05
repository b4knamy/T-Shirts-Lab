<?php

namespace App\Repositories\Eloquent;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;

class OrderRepository implements OrderRepositoryInterface
{
    public function findById(string $id): ?Order
    {
        return Order::with(['items.product.images', 'items.design', 'payment', 'user'])
            ->find($id);
    }

    public function paginateAll(int $page, int $limit): array
    {
        $query = Order::with(['items.product', 'payment', 'user'])
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $orders = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return compact('orders', 'total');
    }

    public function paginateForUser(string $userId, int $page, int $limit): array
    {
        $query = Order::with(['items.product', 'payment'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        $total = $query->count();
        $orders = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return compact('orders', 'total');
    }

    public function create(array $data): Order
    {
        return Order::create($data);
    }

    public function updateStatus(string $id, string $status, ?string $adminNotes = null): Order
    {
        $order = Order::with(['items.product', 'payment'])->findOrFail($id);

        $updateData = ['status' => $status];
        if ($adminNotes !== null) {
            $updateData['admin_notes'] = $adminNotes;
        }

        $order->update($updateData);
        $order->refresh();

        return $order;
    }
}
