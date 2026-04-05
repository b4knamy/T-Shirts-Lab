<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        $unitPrice = fake()->randomFloat(2, 39.90, 199.90);
        $quantity = fake()->numberBetween(1, 5);
        $totalPrice = round($unitPrice * $quantity, 2);

        return [
            'order_id' => Order::factory(),
            'product_id' => Product::factory(),
            'design_id' => null,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'customization_data' => null,
        ];
    }

    public function withCustomization(): static
    {
        return $this->state(fn () => [
            'customization_data' => json_encode([
                'text' => fake()->words(3, true),
                'color' => fake()->hexColor(),
            ]),
        ]);
    }
}
