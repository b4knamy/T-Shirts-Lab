<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                => $this->id,
      'productId'         => $this->product_id,
      'designId'          => $this->design_id,
      'quantity'          => $this->quantity,
      'unitPrice'         => (float) $this->unit_price,
      'totalPrice'        => (float) $this->total_price,
      'customizationData' => $this->customization_data,
      'product'           => $this->whenLoaded('product', fn() => [
        'id'    => $this->product->id,
        'name'  => $this->product->name,
        'slug'  => $this->product->slug,
        'price' => (float) $this->product->price,
        'image' => $this->product->images->first()?->image_url,
      ]),
      'design'            => new DesignResource($this->whenLoaded('design')),
    ];
  }
}
