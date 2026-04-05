<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Fixed super admin account
        User::firstOrCreate(
            ['email' => 'superadmin@tshirtslab.com'],
            [
                'password_hash' => Hash::make('Super@123'),
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'role' => 'SUPER_ADMIN',
                'is_active' => true,
            ]
        );

        // Fixed admin account
        User::firstOrCreate(
            ['email' => 'admin@tshirtslab.com'],
            [
                'password_hash' => Hash::make('Admin@123'),
                'first_name' => 'Admin',
                'last_name' => 'TShirtsLab',
                'role' => 'ADMIN',
                'is_active' => true,
            ]
        );

        // Fixed moderator account
        User::firstOrCreate(
            ['email' => 'moderator@tshirtslab.com'],
            [
                'password_hash' => Hash::make('Mod@123'),
                'first_name' => 'Moderador',
                'last_name' => 'TShirtsLab',
                'role' => 'MODERATOR',
                'is_active' => true,
            ]
        );

        // Fixed test customer
        User::firstOrCreate(
            ['email' => 'customer@tshirtslab.com'],
            [
                'password_hash' => Hash::make('Customer@123'),
                'first_name' => 'João',
                'last_name' => 'Silva',
                'role' => 'CUSTOMER',
                'is_active' => true,
            ]
        );

        // Random customers for development (more for reviews/orders)
        User::factory()->count(20)->create();

        $this->command->info('✅ UserSeeder: super_admin + admin + moderator + customer + 20 random users created.');
    }
}
