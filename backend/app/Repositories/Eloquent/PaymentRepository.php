<?php

namespace App\Repositories\Eloquent;

use App\Models\Payment;
use App\Repositories\Contracts\PaymentRepositoryInterface;

class PaymentRepository implements PaymentRepositoryInterface
{
    public function findByPaymentIntentId(string $paymentIntentId): ?Payment
    {
        return Payment::where('stripe_payment_intent_id', $paymentIntentId)->first();
    }

    public function createOrUpdate(string $orderId, array $data): Payment
    {
        return Payment::updateOrCreate(
            ['order_id' => $orderId],
            $data
        );
    }

    public function update(string $paymentIntentId, array $data): Payment
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)->firstOrFail();
        $payment->update($data);
        $payment->refresh();

        return $payment;
    }
}
