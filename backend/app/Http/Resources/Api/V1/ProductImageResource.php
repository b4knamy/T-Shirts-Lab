<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'         => $this->id,
      'image_url'  => $this->image_url,
      'alt_text'   => $this->alt_text,
      'sort_order' => $this->sort_order,
      'is_primary' => $this->is_primary,
    ];
  }
}
