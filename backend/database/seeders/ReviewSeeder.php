<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
  /**
   * Sample review comments grouped by rating range.
   */
  private array $comments = [
    5 => [
      'Camiseta incrível! Qualidade top e o tecido é muito confortável.',
      'Amei! Ficou perfeita. Já quero comprar outra.',
      'Melhor camiseta que já comprei. Estampa perfeita e não desbota.',
      'Superou minhas expectativas. Material premium de verdade!',
      'Show demais! Chegou antes do prazo e a qualidade é nota 10.',
      'Presente perfeito! A pessoa amou. Voltarei a comprar com certeza.',
      'Tecido super macio e a estampa ficou idêntica à foto. Parabéns!',
      'Comprei pra mim e já encomendei mais duas pra amigos. Excelente!',
    ],
    4 => [
      'Muito boa! Apenas achei o tamanho um pouco grande.',
      'Gostei bastante. A estampa é bonita, tecido confortável.',
      'Boa qualidade no geral. Cor um pouquinho diferente da foto.',
      'Bonita e confortável. Entrega dentro do prazo.',
      'Camiseta legal, material bom. Valeu o preço.',
      'Curti o design. Só achei que poderia ser um pouco mais grossa.',
    ],
    3 => [
      'Razoável. A estampa é bonita mas o tecido é fino demais.',
      'OK pelo preço. Nada excepcional mas também não é ruim.',
      'Camiseta regular. Esperava mais pela descrição do produto.',
      'Design legal mas a qualidade do tecido poderia melhorar.',
    ],
    2 => [
      'Não gostei muito. Tamanho veio diferente do pedido.',
      'A estampa desbotou depois de poucas lavagens.',
      'Tecido de qualidade baixa. Não recomendo.',
    ],
    1 => [
      'Péssima qualidade. A estampa saiu na primeira lavagem.',
      'Veio com defeito e o atendimento não resolveu.',
    ],
  ];

  /** Sample admin replies for some reviews. */
  private array $adminReplies = [
    'Obrigado pelo feedback! Ficamos felizes que gostou. 🙌',
    'Agradecemos a avaliação! Esperamos vê-lo novamente.',
    'Sentimos muito pela experiência. Entre em contato conosco para resolver!',
    'Obrigado! Sua opinião é muito importante para nós.',
    'Lamentamos o ocorrido. Vamos melhorar! Entre em contato pelo suporte.',
    'Que bom que curtiu! Volte sempre 😊',
  ];

  public function run(): void
  {
    $customers = User::where('role', 'CUSTOMER')->get();
    $products  = Product::all();

    if ($customers->isEmpty() || $products->isEmpty()) {
      $this->command->warn('⚠️  ReviewSeeder: Needs customers and products first. Skipping.');
      return;
    }

    $count = 0;

    foreach ($products as $product) {
      // Each product gets 0–6 reviews
      $numReviews = rand(0, 6);
      $reviewers  = $customers->random(min($numReviews, $customers->count()));

      foreach ($reviewers as $customer) {
        // Weighted random rating: more 4s and 5s than 1s and 2s
        $rating = $this->weightedRating();
        $commentPool = $this->comments[$rating];
        $comment = $commentPool[array_rand($commentPool)];

        // ~30% chance of admin reply
        $hasReply      = rand(1, 100) <= 30;
        $adminReply    = $hasReply ? $this->adminReplies[array_rand($this->adminReplies)] : null;
        $adminRepliedAt = $hasReply ? now()->subDays(rand(0, 10)) : null;

        ProductReview::firstOrCreate(
          [
            'user_id'    => $customer->id,
            'product_id' => $product->id,
          ],
          [
            'rating'           => $rating,
            'comment'          => $comment,
            'admin_reply'      => $adminReply,
            'admin_replied_at' => $adminRepliedAt,
            'created_at'       => now()->subDays(rand(1, 90)),
          ]
        );
        $count++;
      }
    }

    $this->command->info("✅ ReviewSeeder: {$count} product reviews created.");
  }

  /**
   * Weighted random: higher ratings are more likely.
   * Distribution: 5→35%, 4→30%, 3→20%, 2→10%, 1→5%
   */
  private function weightedRating(): int
  {
    $rand = rand(1, 100);

    return match (true) {
      $rand <= 5  => 1,
      $rand <= 15 => 2,
      $rand <= 35 => 3,
      $rand <= 65 => 4,
      default     => 5,
    };
  }
}
