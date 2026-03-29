<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'              => $this->id,
      'sku'             => $this->sku,
      'name'            => $this->name,
      'slug'            => $this->slug,
      'description'     => $this->description,
      'longDescription' => $this->long_description,
      'categoryId'      => $this->category_id,
      'category'        => new CategoryResource($this->whenLoaded('category')),
      'price'           => (float) $this->price,
      'costPrice'       => $this->cost_price ? (float) $this->cost_price : null,
      'discountPrice'   => $this->discount_price ? (float) $this->discount_price : null,
      'discountPercent' => $this->discount_percent ? (float) $this->discount_percent : null,
      'stockQuantity'   => $this->stock_quantity,
      'reservedQuantity' => $this->reserved_quantity,
      'status'          => $this->status,
      'isFeatured'      => $this->is_featured,
      'color'           => $this->color,
      'size'            => $this->size,
      'images'          => ProductImageResource::collection($this->whenLoaded('images')),
      'designs'         => DesignResource::collection($this->whenLoaded('designs')),
      'createdAt'       => $this->created_at?->toISOString(),
      'updatedAt'       => $this->updated_at?->toISOString(),
    ];
  }
}
