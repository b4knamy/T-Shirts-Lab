<?php

namespace Database\Factories;

use App\Models\Design;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Design>
 */
class DesignFactory extends Factory
{
    protected $model = Design::class;

    private static array $categories = [
        'anime',
        'games',
        'filmes',
        'series',
        'musica',
        'esportes',
        'minimalista',
        'abstrato',
        'tipografia',
        'natureza',
    ];

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'name' => ucwords(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'image_url' => 'https://placehold.co/600x600/png',
            'category' => fake()->randomElement(self::$categories),
            'is_approved' => fake()->boolean(80),
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => ['is_approved' => true]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['is_approved' => false]);
    }
}
