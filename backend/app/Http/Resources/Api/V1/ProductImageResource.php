<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'        => $this->id,
      'imageUrl'  => $this->image_url,
      'altText'   => $this->alt_text,
      'sortOrder' => $this->sort_order,
      'isPrimary' => $this->is_primary,
    ];
  }
}
