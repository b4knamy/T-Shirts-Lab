<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    $avatarUrl = $this->user?->profile_picture_url;
    if ($avatarUrl && !str_starts_with($avatarUrl, 'http')) {
      $avatarUrl = url('storage/' . ltrim($avatarUrl, '/'));
    }

    return [
      'id'               => $this->id,
      'user_id'          => $this->user_id,
      'product_id'       => $this->product_id,
      'rating'           => $this->rating,
      'comment'          => $this->comment,
      'admin_reply'      => $this->admin_reply,
      'admin_replied_at' => $this->admin_replied_at?->toISOString(),
      'user'             => [
        'id'                  => $this->user?->id,
        'first_name'          => $this->user?->first_name,
        'last_name'           => $this->user?->last_name,
        'profile_picture_url' => $avatarUrl,
      ],
      'created_at'       => $this->created_at?->toISOString(),
      'updated_at'       => $this->updated_at?->toISOString(),
    ];
  }
}
