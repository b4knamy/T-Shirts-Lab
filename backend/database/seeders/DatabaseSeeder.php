<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Order matters: Users → Categories → Products → Orders → Coupons (FK dependencies).
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            OrderSeeder::class,
            CouponSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('🌱 Database seeded successfully!');
        $this->command->info('   Admin:    admin@tshirtslab.com    / Admin@123');
        $this->command->info('   Customer: customer@tshirtslab.com / Customer@123');
        $this->command->info('');
        $this->command->info('   Coupons: WELCOME10, FRETE0, SUPER25, VIP20, FLASH50');
    }
}
