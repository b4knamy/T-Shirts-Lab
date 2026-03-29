<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'              => $this->id,
      'orderNumber'     => $this->order_number,
      'userId'          => $this->user_id,
      'subtotal'        => (float) $this->subtotal,
      'discountAmount'  => (float) $this->discount_amount,
      'taxAmount'       => (float) $this->tax_amount,
      'shippingCost'    => (float) $this->shipping_cost,
      'total'           => (float) $this->total,
      'status'          => $this->status,
      'paymentStatus'   => $this->payment_status,
      'customerNotes'   => $this->customer_notes,
      'adminNotes'      => $this->admin_notes,
      'items'           => OrderItemResource::collection($this->whenLoaded('items')),
      'payment'         => new PaymentResource($this->whenLoaded('payment')),
      'user'            => new UserResource($this->whenLoaded('user')),
      'createdAt'       => $this->created_at?->toISOString(),
      'updatedAt'       => $this->updated_at?->toISOString(),
    ];
  }
}
