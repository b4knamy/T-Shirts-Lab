<?php

namespace App\Logging\Loggers;

use App\Models\Order;

class OrderLogger extends BaseLogger
{
    protected function channel(): string
    {
        return 'order';
    }

    public function created(Order $order, string $userId, ?string $couponCode): void
    {
        $this->info('order.created', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'user_id' => $userId,
            'subtotal' => (float) $order->subtotal,
            'discount' => (float) $order->discount_amount,
            'total' => (float) $order->total,
            'items_count' => $order->items->count(),
            'coupon_code' => $couponCode,
        ]);
    }

    public function statusUpdated(string $orderId, string $orderNumber, string $previousStatus, string $newStatus, ?string $adminNotes): void
    {
        $this->info('order.status_updated', [
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'admin_notes' => $adminNotes,
        ]);
    }
}
