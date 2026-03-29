<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters: Users → Categories → Products (FK dependencies).
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('🌱 Database seeded successfully!');
        $this->command->info('   Admin:    admin@tshirtslab.com    / Admin@123');
        $this->command->info('   Customer: customer@tshirtslab.com / Customer@123');
    }
}
