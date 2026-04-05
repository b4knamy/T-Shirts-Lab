<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserAddress>
 */
class UserAddressFactory extends Factory
{
    protected $model = UserAddress::class;

    private static array $brazilianStates = [
        'AC',
        'AL',
        'AM',
        'AP',
        'BA',
        'CE',
        'DF',
        'ES',
        'GO',
        'MA',
        'MG',
        'MS',
        'MT',
        'PA',
        'PB',
        'PE',
        'PI',
        'PR',
        'RJ',
        'RN',
        'RO',
        'RR',
        'RS',
        'SC',
        'SE',
        'SP',
        'TO',
    ];

    private static array $cities = [
        'São Paulo',
        'Rio de Janeiro',
        'Belo Horizonte',
        'Salvador',
        'Fortaleza',
        'Curitiba',
        'Manaus',
        'Recife',
        'Porto Alegre',
        'Goiânia',
        'Belém',
        'Florianópolis',
        'Maceió',
        'Natal',
    ];

    private static array $labels = ['Casa', 'Trabalho', 'Apartamento', 'Outro'];

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(self::$labels),
            'street' => 'Rua '.ucwords(fake()->words(3, true)),
            'number' => (string) fake()->numberBetween(1, 9999),
            'complement' => fake()->boolean(30) ? 'Apto '.fake()->numberBetween(1, 200) : null,
            'neighborhood' => ucwords(fake()->words(2, true)),
            'city' => fake()->randomElement(self::$cities),
            'state' => fake()->randomElement(self::$brazilianStates),
            'zip_code' => fake()->numerify('#####-###'),
            'country' => 'BR',
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn () => ['is_default' => true]);
    }
}
