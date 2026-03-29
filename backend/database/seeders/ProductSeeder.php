<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Design;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
  private array $products = [
    [
      'name'           => 'Dragon Ball Z - Goku SSJ',
      'description'    => 'Camiseta estampada Dragon Ball Z com Goku Super Saiyajin.',
      'category_slug'  => 'anime',
      'price'          => 79.90,
      'stock_quantity' => 50,
      'is_featured'    => true,
    ],
    [
      'name'           => 'Naruto Shippuden - Akatsuki',
      'description'    => 'Camiseta preta com estampa da Akatsuki de Naruto Shippuden.',
      'category_slug'  => 'anime',
      'price'          => 89.90,
      'stock_quantity' => 40,
      'is_featured'    => true,
    ],
    [
      'name'           => 'One Piece - Luffy Gear 5',
      'description'    => 'Camiseta branca Luffy Gear 5, design exclusivo One Piece.',
      'category_slug'  => 'anime',
      'price'          => 99.90,
      'stock_quantity' => 30,
      'is_featured'    => true,
    ],
    [
      'name'           => 'The Legend of Zelda',
      'description'    => 'Camiseta verde Zelda com Triforce dourada.',
      'category_slug'  => 'games',
      'price'          => 74.90,
      'stock_quantity' => 45,
      'is_featured'    => true,
    ],
    [
      'name'           => 'Star Wars - Darth Vader',
      'description'    => 'Camiseta Star Wars clássica com Darth Vader.',
      'category_slug'  => 'filmes-series',
      'price'          => 69.90,
      'stock_quantity' => 60,
      'is_featured'    => false,
    ],
    [
      'name'           => 'Minimal Wave',
      'description'    => 'Camiseta design minimalista com onda japonesa.',
      'category_slug'  => 'minimalista',
      'price'          => 59.90,
      'stock_quantity' => 70,
      'is_featured'    => true,
    ],
    [
      'name'           => 'Custom Blank Premium',
      'description'    => 'Camiseta premium em branco para personalização completa.',
      'category_slug'  => 'customizavel',
      'price'          => 49.90,
      'stock_quantity' => 100,
      'is_featured'    => false,
    ],
    [
      'name'           => 'Attack on Titan - Survey Corps',
      'description'    => 'Camiseta Attack on Titan com emblema da Survey Corps.',
      'category_slug'  => 'anime',
      'price'          => 84.90,
      'stock_quantity' => 35,
      'is_featured'    => true,
    ],
  ];

  public function run(): void
  {
    // Pre-load categories indexed by slug
    $categories = Category::whereIn(
      'slug',
      array_column($this->products, 'category_slug')
    )->get()->keyBy('slug');

    foreach ($this->products as $data) {
      $category = $categories[$data['category_slug']];

      $slug = Str::slug($data['name']);

      /** @var Product $product */
      $product = Product::firstOrCreate(
        ['slug' => $slug],
        [
          'sku'            => strtoupper('TSL-' . Str::random(8)),
          'name'           => $data['name'],
          'slug'           => $slug,
          'description'    => $data['description'],
          'category_id'    => $category->id,
          'price'          => $data['price'],
          'stock_quantity' => $data['stock_quantity'],
          'status'         => 'ACTIVE',
          'is_featured'    => $data['is_featured'],
        ]
      );

      // Primary product image (idempotent)
      ProductImage::firstOrCreate(
        ['product_id' => $product->id, 'is_primary' => true],
        [
          'image_url'  => 'https://placehold.co/600x800/333/fff?text=' . urlencode($data['name']),
          'alt_text'   => $data['name'],
          'sort_order' => 1,
          'is_primary' => true,
        ]
      );

      // Default approved design
      Design::firstOrCreate(
        ['product_id' => $product->id, 'name' => 'Design Original - ' . $data['name']],
        [
          'product_id'  => $product->id,
          'name'        => 'Design Original - ' . $data['name'],
          'description' => 'Design padrão do produto.',
          'image_url'   => 'https://placehold.co/400x400/555/fff?text=Design',
          'category'    => $category->name,
          'is_approved' => true,
        ]
      );
    }

    $this->command->info('✅ ProductSeeder: 8 products with images and designs created.');
  }
}
