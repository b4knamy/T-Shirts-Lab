<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
  use HasFactory, HasUuids;

  protected $fillable = [
    'order_id',
    'product_id',
    'design_id',
    'quantity',
    'unit_price',
    'total_price',
    'customization_data',
  ];

  protected function casts(): array
  {
    return [
      'quantity' => 'integer',
      'unit_price' => 'decimal:2',
      'total_price' => 'decimal:2',
      'customization_data' => 'array',
    ];
  }

  public function order()
  {
    return $this->belongsTo(Order::class);
  }

  public function product()
  {
    return $this->belongsTo(Product::class);
  }

  public function design()
  {
    return $this->belongsTo(Design::class);
  }
}
