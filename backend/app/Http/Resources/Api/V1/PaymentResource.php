<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                        => $this->id,
      'order_id'                  => $this->order_id,
      'stripe_payment_intent_id'  => $this->stripe_payment_intent_id,
      'amount'                    => (float) $this->amount,
      'currency'                  => $this->currency,
      'status'                    => $this->status,
      'payment_method'            => $this->payment_method,
      'refund_amount'             => $this->refund_amount ? (float) $this->refund_amount : null,
      'refunded_at'               => $this->refunded_at?->toISOString(),
      'paid_at'                   => $this->paid_at?->toISOString(),
    ];
  }
}
