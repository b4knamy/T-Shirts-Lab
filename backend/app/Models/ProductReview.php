<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
  use HasFactory, HasUuids;

  protected $fillable = [
    'user_id',
    'product_id',
    'rating',
    'comment',
    'admin_reply',
    'admin_replied_at',
  ];

  protected function casts(): array
  {
    return [
      'rating'           => 'integer',
      'admin_replied_at' => 'datetime',
    ];
  }

  public function user()
  {
    return $this->belongsTo(User::class);
  }

  public function product()
  {
    return $this->belongsTo(Product::class);
  }
}
