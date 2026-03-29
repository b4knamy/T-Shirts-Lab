<?php

namespace App\Repositories\Contracts;

interface PaymentRepositoryInterface
{
  public function findByPaymentIntentId(string $paymentIntentId): ?\App\Models\Payment;

  public function createOrUpdate(string $orderId, array $data): \App\Models\Payment;

  public function update(string $paymentIntentId, array $data): \App\Models\Payment;
}
