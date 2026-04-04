<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'email'               => fake()->unique()->safeEmail(),
            'password_hash'       => Hash::make('password'),
            'first_name'          => fake()->firstName(),
            'last_name'           => fake()->lastName(),
            'phone'               => fake()->numerify('(##) #####-####'),
            'role'                => 'CUSTOMER',
            'is_active'           => true,
            'profile_picture_url' => null,
            'refresh_token'       => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn() => [
            'role' => 'ADMIN',
        ]);
    }

    public function superAdmin(): static
    {
        return $this->state(fn() => [
            'role' => 'SUPER_ADMIN',
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn() => [
            'role' => 'MODERATOR',
        ]);
    }

    public function vendor(): static
    {
        return $this->state(fn() => [
            'role' => 'VENDOR',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn() => [
            'is_active' => false,
        ]);
    }
}
