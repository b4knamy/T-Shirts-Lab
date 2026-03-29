<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'sku',
        'name',
        'slug',
        'description',
        'long_description',
        'category_id',
        'price',
        'cost_price',
        'discount_price',
        'discount_percent',
        'stock_quantity',
        'reserved_quantity',
        'status',
        'is_featured',
        'color',
        'size',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'stock_quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'is_featured' => 'boolean',
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function designs()
    {
        return $this->hasMany(Design::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }
}
