<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                     => $this->id,
      'orderId'                => $this->order_id,
      'stripePaymentIntentId'  => $this->stripe_payment_intent_id,
      'amount'                 => (float) $this->amount,
      'currency'               => $this->currency,
      'status'                 => $this->status,
      'paymentMethod'          => $this->payment_method,
      'refundAmount'           => $this->refund_amount ? (float) $this->refund_amount : null,
      'refundedAt'             => $this->refunded_at?->toISOString(),
      'paidAt'                 => $this->paid_at?->toISOString(),
    ];
  }
}
