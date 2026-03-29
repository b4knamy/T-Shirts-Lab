<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
  public function run(): void
  {
    // Fixed admin account
    User::firstOrCreate(
      ['email' => 'admin@tshirtslab.com'],
      [
        'password_hash' => Hash::make('Admin@123'),
        'first_name'    => 'Admin',
        'last_name'     => 'TShirtsLab',
        'role'          => 'ADMIN',
        'is_active'     => true,
      ]
    );

    // Fixed test customer
    User::firstOrCreate(
      ['email' => 'customer@tshirtslab.com'],
      [
        'password_hash' => Hash::make('Customer@123'),
        'first_name'    => 'João',
        'last_name'     => 'Silva',
        'role'          => 'CUSTOMER',
        'is_active'     => true,
      ]
    );

    // Random customers for development
    User::factory()->count(10)->create();

    $this->command->info('✅ UserSeeder: admin + customer + 10 random users created.');
  }
}
