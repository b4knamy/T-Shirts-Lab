<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    private static array $names = [
        'Anime',
        'Games',
        'Filmes & Séries',
        'Minimalista',
        'Customizável',
        'Esportes',
        'Música',
        'Natureza',
        'Tecnologia',
        'Arte',
    ];

    public function definition(): array
    {
        $name = fake()->unique()->randomElement(self::$names);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'image_url' => 'https://placehold.co/400x300/333/fff?text='.urlencode($name),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
