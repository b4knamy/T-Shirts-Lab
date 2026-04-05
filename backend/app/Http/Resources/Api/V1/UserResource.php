<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $avatarUrl = $this->profile_picture_url;
        if ($avatarUrl && ! str_starts_with($avatarUrl, 'http')) {
            $avatarUrl = url('storage/'.ltrim($avatarUrl, '/'));
        }

        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'phone' => $this->phone,
            'role' => $this->role,
            'is_active' => $this->is_active,
            'profile_picture_url' => $avatarUrl,
            'addresses' => $this->whenLoaded('addresses'),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
