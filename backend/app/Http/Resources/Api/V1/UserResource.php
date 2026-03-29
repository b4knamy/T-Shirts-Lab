<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
  public function toArray(Request $request): array
  {
    return [
      'id'                => $this->id,
      'email'             => $this->email,
      'firstName'         => $this->first_name,
      'lastName'          => $this->last_name,
      'phone'             => $this->phone,
      'role'              => $this->role,
      'isActive'          => $this->is_active,
      'profilePictureUrl' => $this->profile_picture_url,
      'createdAt'         => $this->created_at?->toISOString(),
    ];
  }
}
