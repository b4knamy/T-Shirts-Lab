<?php

namespace App\Repositories\Contracts;

use App\Models\Payment;

interface PaymentRepositoryInterface
{
    public function findByPaymentIntentId(string $paymentIntentId): ?Payment;

    public function createOrUpdate(string $orderId, array $data): Payment;

    public function update(string $paymentIntentId, array $data): Payment;
}
