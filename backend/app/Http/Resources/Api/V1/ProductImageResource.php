<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductImageResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $url = $this->image_url;

    // If the URL is a relative path (from old storage), make it absolute
    if ($url && !str_starts_with($url, 'http')) {
      $url = url('storage/' . ltrim($url, '/'));
    }

    return [
      'id'         => $this->id,
      'image_url'  => $url,
      'alt_text'   => $this->alt_text,
      'sort_order' => $this->sort_order,
      'is_primary' => $this->is_primary,
    ];
  }
}
