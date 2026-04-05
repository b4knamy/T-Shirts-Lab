<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    private array $categories = [
        [
            'name' => 'Anime',
            'slug' => 'anime',
            'description' => 'Camisetas de anime e mangá',
            'is_active' => true,
        ],
        [
            'name' => 'Games',
            'slug' => 'games',
            'description' => 'Camisetas de jogos e games',
            'is_active' => true,
        ],
        [
            'name' => 'Filmes & Séries',
            'slug' => 'filmes-series',
            'description' => 'Camisetas de filmes e séries',
            'is_active' => true,
        ],
        [
            'name' => 'Minimalista',
            'slug' => 'minimalista',
            'description' => 'Designs minimalistas e clean',
            'is_active' => true,
        ],
        [
            'name' => 'Customizável',
            'slug' => 'customizavel',
            'description' => 'Camisetas para personalização',
            'is_active' => true,
        ],
    ];

    public function run(): void
    {
        foreach ($this->categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }

        $this->command->info('✅ CategorySeeder: 5 categories created.');
    }
}
