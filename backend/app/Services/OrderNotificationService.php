<?php

namespace App\Services;

use App\Logging\Loggers\OrderLogger;
use App\Mail\OrderStatusMail;
use App\Mail\PaymentFailedMail;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Throwable;

class OrderNotificationService
{
    public function __construct(private readonly OrderLogger $orderLogger) {}

    public function sendCreated(Order $order): void
    {
        $this->sendStatusUpdate($order, newStatus: 'PENDING');
    }

    public function sendStatusUpdate(Order $order, string $newStatus, ?string $previousStatus = null, ?string $adminNotes = null): void
    {
        if ($previousStatus !== null && $previousStatus === $newStatus) {
            return;
        }

        $order->loadMissing('user');
        $user = $order->user;

        if (! $user || empty($user->email)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new OrderStatusMail(
                firstName: $user->first_name ?? 'there',
                orderId: $order->id,
                orderNumber: $order->order_number,
                total: (float) $order->total,
                currentStatus: $newStatus,
                previousStatus: $previousStatus,
                adminNotes: $adminNotes,
            ));
        } catch (Throwable $e) {
            $this->orderLogger->exception('order.status_email_failed', $e, [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'email' => $user->email,
                'previous_status' => $previousStatus,
                'new_status' => $newStatus,
            ]);
        }
    }

    public function sendPaymentFailed(Order $order, ?string $failureReason = null): void
    {
        $order->loadMissing('user');
        $user = $order->user;

        if (! $user || empty($user->email)) {
            return;
        }

        try {
            Mail::to($user->email)->send(new PaymentFailedMail(
                firstName: $user->first_name ?? 'there',
                orderNumber: $order->order_number,
                total: (float) $order->total,
                failureReason: $failureReason,
                orderId: $order->id,
            ));
        } catch (Throwable $e) {
            $this->orderLogger->exception('order.payment_failed_email_failed', $e, [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'user_id' => $user->id,
                'email' => $user->email,
                'failure_reason' => $failureReason,
            ]);
        }
    }
}
