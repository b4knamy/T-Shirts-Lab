<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'               => $this->id,
      'sku'              => $this->sku,
      'name'             => $this->name,
      'slug'             => $this->slug,
      'description'      => $this->description,
      'long_description' => $this->long_description,
      'category_id'      => $this->category_id,
      'category'         => new CategoryResource($this->whenLoaded('category')),
      'price'            => (float) $this->price,
      'cost_price'       => $this->cost_price ? (float) $this->cost_price : null,
      'discount_price'   => $this->discount_price ? (float) $this->discount_price : null,
      'discount_percent' => $this->discount_percent ? (float) $this->discount_percent : null,
      'stock_quantity'   => $this->stock_quantity,
      'reserved_quantity' => $this->reserved_quantity,
      'status'           => $this->status,
      'is_featured'      => $this->is_featured,
      'color'            => $this->color,
      'size'             => $this->size,
      'images'           => ProductImageResource::collection($this->whenLoaded('images')),
      'designs'          => DesignResource::collection($this->whenLoaded('designs')),
      'created_at'       => $this->created_at?->toISOString(),
      'updated_at'       => $this->updated_at?->toISOString(),
    ];
  }
}
