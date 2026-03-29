<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
  use HasFactory, HasUuids;

  protected $fillable = [
    'user_id',
    'label',
    'street',
    'number',
    'complement',
    'neighborhood',
    'city',
    'state',
    'zip_code',
    'country',
    'is_default',
  ];

  protected function casts(): array
  {
    return [
      'is_default' => 'boolean',
    ];
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }
}
