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
    // ── ANIME (15 products) ────────────────────────────────────
    ['name' => 'Dragon Ball Z - Goku SSJ',             'description' => 'Camiseta estampada Dragon Ball Z com Goku Super Saiyajin em pose icônica.', 'category_slug' => 'anime', 'price' => 79.90, 'stock_quantity' => 50, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Naruto Shippuden - Akatsuki',           'description' => 'Camiseta preta com estampa da Akatsuki de Naruto Shippuden.', 'category_slug' => 'anime', 'price' => 89.90, 'stock_quantity' => 40, 'is_featured' => true,  'discount_percent' => 10],
    ['name' => 'One Piece - Luffy Gear 5',              'description' => 'Camiseta branca Luffy Gear 5, design exclusivo One Piece.', 'category_slug' => 'anime', 'price' => 99.90, 'stock_quantity' => 30, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Attack on Titan - Survey Corps',        'description' => 'Camiseta Attack on Titan com emblema da Survey Corps.', 'category_slug' => 'anime', 'price' => 84.90, 'stock_quantity' => 35, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Demon Slayer - Tanjiro',                'description' => 'Camiseta com estampa de Tanjiro Kamado usando a respiração da água.', 'category_slug' => 'anime', 'price' => 89.90, 'stock_quantity' => 45, 'is_featured' => false, 'discount_percent' => 15],
    ['name' => 'Jujutsu Kaisen - Gojo Satoru',          'description' => 'Camiseta preta com Gojo Satoru e seus olhos Six Eyes.', 'category_slug' => 'anime', 'price' => 94.90, 'stock_quantity' => 38, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'My Hero Academia - All Might',          'description' => 'Camiseta azul com All Might no estilo Plus Ultra.', 'category_slug' => 'anime', 'price' => 79.90, 'stock_quantity' => 42, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Death Note - Ryuk',                     'description' => 'Camiseta com o shinigami Ryuk em fundo escuro.', 'category_slug' => 'anime', 'price' => 74.90, 'stock_quantity' => 55, 'is_featured' => false, 'discount_percent' => 20],
    ['name' => 'Fullmetal Alchemist - Transmutation',   'description' => 'Camiseta com o círculo de transmutação de Fullmetal Alchemist.', 'category_slug' => 'anime', 'price' => 84.90, 'stock_quantity' => 30, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Evangelion - Unit 01',                   'description' => 'Camiseta com o EVA-01 roxo e verde em modo berserker.', 'category_slug' => 'anime', 'price' => 92.90, 'stock_quantity' => 25, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Chainsaw Man - Pochita',                'description' => 'Camiseta com Pochita, o demônio motosserra fofo.', 'category_slug' => 'anime', 'price' => 79.90, 'stock_quantity' => 48, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Spy x Family - Anya',                   'description' => 'Camiseta rosa com Anya Forger e sua expressão clássica.', 'category_slug' => 'anime', 'price' => 74.90, 'stock_quantity' => 60, 'is_featured' => false, 'discount_percent' => 10],
    ['name' => 'Dragon Ball Super - Vegeta Ultra Ego',  'description' => 'Camiseta com Vegeta na forma Ultra Ego, detalhes em roxo.', 'category_slug' => 'anime', 'price' => 89.90, 'stock_quantity' => 32, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Bleach - Ichigo Bankai',                'description' => 'Camiseta Bleach com Ichigo Kurosaki em modo Bankai.', 'category_slug' => 'anime', 'price' => 84.90, 'stock_quantity' => 28, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Hunter x Hunter - Killua',              'description' => 'Camiseta com Killua Zoldyck usando Godspeed.', 'category_slug' => 'anime', 'price' => 87.90, 'stock_quantity' => 36, 'is_featured' => true,  'discount_percent' => 5],

    // ── GAMES (12 products) ────────────────────────────────────
    ['name' => 'The Legend of Zelda - Triforce',        'description' => 'Camiseta verde Zelda com Triforce dourada.', 'category_slug' => 'games', 'price' => 74.90, 'stock_quantity' => 45, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'God of War - Kratos',                   'description' => 'Camiseta com Kratos e o machado Leviatã.', 'category_slug' => 'games', 'price' => 89.90, 'stock_quantity' => 40, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Elden Ring - Tarnished',                'description' => 'Camiseta Elden Ring com o Tarnished e a Erdtree ao fundo.', 'category_slug' => 'games', 'price' => 94.90, 'stock_quantity' => 30, 'is_featured' => false, 'discount_percent' => 15],
    ['name' => 'Dark Souls - Bonfire',                  'description' => 'Camiseta com a icônica fogueira de Dark Souls.', 'category_slug' => 'games', 'price' => 79.90, 'stock_quantity' => 35, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Minecraft - Creeper',                   'description' => 'Camiseta verde com o rosto do Creeper pixelado.', 'category_slug' => 'games', 'price' => 59.90, 'stock_quantity' => 80, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Hollow Knight - The Knight',            'description' => 'Camiseta com The Knight de Hollow Knight em estilo artístico.', 'category_slug' => 'games', 'price' => 79.90, 'stock_quantity' => 42, 'is_featured' => true,  'discount_percent' => 10],
    ['name' => 'Pokémon - Pikachu Retro',               'description' => 'Camiseta retro com Pikachu estilo 8-bit clássico.', 'category_slug' => 'games', 'price' => 69.90, 'stock_quantity' => 65, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Resident Evil - Umbrella Corp',         'description' => 'Camiseta preta com o logo da Umbrella Corporation.', 'category_slug' => 'games', 'price' => 74.90, 'stock_quantity' => 50, 'is_featured' => false, 'discount_percent' => 20],
    ['name' => 'Final Fantasy VII - Cloud',             'description' => 'Camiseta com Cloud Strife e a Buster Sword.', 'category_slug' => 'games', 'price' => 84.90, 'stock_quantity' => 38, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Super Mario - Retro Jump',              'description' => 'Camiseta com Mario em pixel art saltando sobre blocos.', 'category_slug' => 'games', 'price' => 64.90, 'stock_quantity' => 70, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Cyberpunk 2077 - Samurai',              'description' => 'Camiseta com o logo da banda Samurai de Cyberpunk 2077.', 'category_slug' => 'games', 'price' => 79.90, 'stock_quantity' => 33, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Hades - Zagreus',                       'description' => 'Camiseta com Zagreus de Hades em arte estilizada.', 'category_slug' => 'games', 'price' => 84.90, 'stock_quantity' => 28, 'is_featured' => false, 'discount_percent' => 10],

    // ── FILMES & SÉRIES (12 products) ──────────────────────────
    ['name' => 'Star Wars - Darth Vader',               'description' => 'Camiseta Star Wars clássica com Darth Vader.', 'category_slug' => 'filmes-series', 'price' => 69.90, 'stock_quantity' => 60, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Marvel - Homem de Ferro',               'description' => 'Camiseta com o reator arc do Homem de Ferro.', 'category_slug' => 'filmes-series', 'price' => 79.90, 'stock_quantity' => 55, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Harry Potter - Hogwarts',               'description' => 'Camiseta com o brasão de Hogwarts das quatro casas.', 'category_slug' => 'filmes-series', 'price' => 74.90, 'stock_quantity' => 50, 'is_featured' => false, 'discount_percent' => 15],
    ['name' => 'Stranger Things - Hellfire Club',       'description' => 'Camiseta preta com o logo do Hellfire Club.', 'category_slug' => 'filmes-series', 'price' => 79.90, 'stock_quantity' => 40, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Breaking Bad - Heisenberg',             'description' => 'Camiseta com o icônico retrato de Heisenberg.', 'category_slug' => 'filmes-series', 'price' => 69.90, 'stock_quantity' => 45, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'The Witcher - Geralt',                  'description' => 'Camiseta com Geralt de Rivia e o medalhão do lobo.', 'category_slug' => 'filmes-series', 'price' => 84.90, 'stock_quantity' => 35, 'is_featured' => false, 'discount_percent' => 10],
    ['name' => 'Batman - Dark Knight',                  'description' => 'Camiseta preta com o símbolo do Batman Dark Knight.', 'category_slug' => 'filmes-series', 'price' => 74.90, 'stock_quantity' => 50, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Senhor dos Anéis - Um Anel',            'description' => 'Camiseta com inscrições élficas do Um Anel.', 'category_slug' => 'filmes-series', 'price' => 79.90, 'stock_quantity' => 38, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Back to the Future - DeLorean',         'description' => 'Camiseta retro com o DeLorean e trilhas de fogo.', 'category_slug' => 'filmes-series', 'price' => 74.90, 'stock_quantity' => 42, 'is_featured' => false, 'discount_percent' => 20],
    ['name' => 'The Mandalorian - This Is The Way',     'description' => 'Camiseta com o capacete do Mandalorian e a frase icônica.', 'category_slug' => 'filmes-series', 'price' => 84.90, 'stock_quantity' => 30, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Peaky Blinders - Tommy Shelby',         'description' => 'Camiseta com Tommy Shelby em estilo vintage.', 'category_slug' => 'filmes-series', 'price' => 79.90, 'stock_quantity' => 36, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Marvel - Spider-Man Miles Morales',     'description' => 'Camiseta com Spider-Man Miles Morales em estilo grafite.', 'category_slug' => 'filmes-series', 'price' => 84.90, 'stock_quantity' => 44, 'is_featured' => true,  'discount_percent' => 5],

    // ── MINIMALISTA (10 products) ──────────────────────────────
    ['name' => 'Minimal Wave',                          'description' => 'Camiseta design minimalista com onda japonesa.', 'category_slug' => 'minimalista', 'price' => 59.90, 'stock_quantity' => 70, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Minimal Mountain',                      'description' => 'Camiseta com montanhas geométricas em linhas finas.', 'category_slug' => 'minimalista', 'price' => 59.90, 'stock_quantity' => 65, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Minimal Sunset',                        'description' => 'Camiseta minimalista com pôr do sol em degradê.', 'category_slug' => 'minimalista', 'price' => 54.90, 'stock_quantity' => 75, 'is_featured' => false, 'discount_percent' => 10],
    ['name' => 'Minimal Forest',                        'description' => 'Camiseta com silhueta de floresta de pinheiros.', 'category_slug' => 'minimalista', 'price' => 59.90, 'stock_quantity' => 60, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Minimal Circuit',                       'description' => 'Camiseta com padrão de circuito eletrônico minimalista.', 'category_slug' => 'minimalista', 'price' => 64.90, 'stock_quantity' => 50, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Minimal Cat',                           'description' => 'Camiseta com gato em traço fino minimalista.', 'category_slug' => 'minimalista', 'price' => 54.90, 'stock_quantity' => 80, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Minimal Moon Phases',                   'description' => 'Camiseta com as fases da lua em estilo clean.', 'category_slug' => 'minimalista', 'price' => 59.90, 'stock_quantity' => 55, 'is_featured' => false, 'discount_percent' => 15],
    ['name' => 'Minimal Geometric Bear',                'description' => 'Camiseta com urso em formas geométricas.', 'category_slug' => 'minimalista', 'price' => 64.90, 'stock_quantity' => 45, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Minimal Coffee',                        'description' => 'Camiseta com xícara de café em design line art.', 'category_slug' => 'minimalista', 'price' => 49.90, 'stock_quantity' => 90, 'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Minimal Astronaut',                     'description' => 'Camiseta com astronauta flutuando em traço minimalista.', 'category_slug' => 'minimalista', 'price' => 64.90, 'stock_quantity' => 40, 'is_featured' => true,  'discount_percent' => 10],

    // ── CUSTOMIZÁVEL (6 products) ──────────────────────────────
    ['name' => 'Custom Blank Premium',                  'description' => 'Camiseta premium em branco para personalização completa.', 'category_slug' => 'customizavel', 'price' => 49.90, 'stock_quantity' => 100, 'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Custom Blank - Preta',                  'description' => 'Camiseta preta básica premium para customização.', 'category_slug' => 'customizavel', 'price' => 49.90, 'stock_quantity' => 90,  'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Custom V-Neck Branca',                  'description' => 'Camiseta gola V branca para personalização.', 'category_slug' => 'customizavel', 'price' => 54.90, 'stock_quantity' => 70,  'is_featured' => false, 'discount_percent' => 10],
    ['name' => 'Custom Oversized',                      'description' => 'Camiseta oversized para customização street style.', 'category_slug' => 'customizavel', 'price' => 59.90, 'stock_quantity' => 60,  'is_featured' => true,  'discount_percent' => null],
    ['name' => 'Custom Long Sleeve',                    'description' => 'Camiseta manga longa para personalização.', 'category_slug' => 'customizavel', 'price' => 64.90, 'stock_quantity' => 50,  'is_featured' => false, 'discount_percent' => null],
    ['name' => 'Custom Crop Top',                       'description' => 'Camiseta cropped feminina para personalização.', 'category_slug' => 'customizavel', 'price' => 54.90, 'stock_quantity' => 55,  'is_featured' => false, 'discount_percent' => 15],
  ];

  /** Placeholder background colors per category for visual variety. */
  private array $categoryColors = [
    'anime'         => ['e63946/fff', 'ff6b35/fff', '1d3557/fff', '457b9d/fff', 'a8dadc/333'],
    'games'         => ['2d6a4f/fff', '40916c/fff', '52b788/fff', '1b4332/fff', '74c69d/333'],
    'filmes-series' => ['6a040f/fff', '9d0208/fff', 'dc2f02/fff', 'e85d04/fff', 'faa307/333'],
    'minimalista'   => ['333/fff',    '555/fff',    '777/fff',    '999/fff',    'bbb/333'],
    'customizavel'  => ['264653/fff', '2a9d8f/fff', 'e9c46a/333', 'f4a261/333', 'e76f51/fff'],
  ];

  public function run(): void
  {
    $categories = Category::whereIn(
      'slug',
      array_unique(array_column($this->products, 'category_slug'))
    )->get()->keyBy('slug');

    $colorIndex = [];

    foreach ($this->products as $data) {
      $category = $categories[$data['category_slug']];
      $slug     = Str::slug($data['name']);

      // Cycle through colors per category
      $ci = $colorIndex[$data['category_slug']] ?? 0;
      $colors = $this->categoryColors[$data['category_slug']];
      $color  = $colors[$ci % count($colors)];
      $colorIndex[$data['category_slug']] = $ci + 1;

      /** @var Product $product */
      $product = Product::firstOrCreate(
        ['slug' => $slug],
        [
          'sku'              => strtoupper('TSL-' . Str::random(8)),
          'name'             => $data['name'],
          'slug'             => $slug,
          'description'      => $data['description'],
          'category_id'      => $category->id,
          'price'            => $data['price'],
          'stock_quantity'   => $data['stock_quantity'],
          'status'           => 'ACTIVE',
          'is_featured'      => $data['is_featured'],
          'discount_percent' => $data['discount_percent'],
        ]
      );

      // Primary product image
      ProductImage::firstOrCreate(
        ['product_id' => $product->id, 'is_primary' => true],
        [
          'image_url'  => 'https://placehold.co/600x800/' . $color . '?text=' . urlencode($data['name']),
          'alt_text'   => $data['name'],
          'sort_order' => 1,
          'is_primary' => true,
        ]
      );

      // Secondary image for variety
      ProductImage::firstOrCreate(
        ['product_id' => $product->id, 'sort_order' => 2],
        [
          'image_url'  => 'https://placehold.co/600x800/' . $color . '?text=' . urlencode('Back'),
          'alt_text'   => $data['name'] . ' - verso',
          'sort_order' => 2,
          'is_primary' => false,
        ]
      );

      // Default approved design
      Design::firstOrCreate(
        ['product_id' => $product->id, 'name' => 'Design Original - ' . $data['name']],
        [
          'product_id'  => $product->id,
          'name'        => 'Design Original - ' . $data['name'],
          'description' => 'Design padrão do produto.',
          'image_url'   => 'https://placehold.co/400x400/' . $color . '?text=Design',
          'category'    => $category->name,
          'is_approved' => true,
        ]
      );
    }

    $count = count($this->products);
    $this->command->info("✅ ProductSeeder: {$count} products with images and designs created.");
  }
}
