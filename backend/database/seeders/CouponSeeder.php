<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    private array $coupons = [
        [
            'code' => 'WELCOME10',
            'description' => '10% de desconto no primeiro pedido!',
            'type' => 'PERCENTAGE',
            'value' => 10,
            'min_order_amount' => 50,
            'max_discount_amount' => 30,
            'per_user_limit' => 1,
            'is_active' => true,
            'is_public' => true,
            'expires_at_days' => 30,
        ],
        [
            'code' => 'FRETE0',
            'description' => 'R$15 off — frete grátis para qualquer pedido!',
            'type' => 'FIXED',
            'value' => 15,
            'min_order_amount' => null,
            'max_discount_amount' => null,
            'per_user_limit' => 2,
            'is_active' => true,
            'is_public' => true,
            'expires_at_days' => 14,
        ],
        [
            'code' => 'SUPER25',
            'description' => '25% de desconto em compras acima de R$200!',
            'type' => 'PERCENTAGE',
            'value' => 25,
            'min_order_amount' => 200,
            'max_discount_amount' => 100,
            'per_user_limit' => 1,
            'is_active' => true,
            'is_public' => true,
            'expires_at_days' => 7,
        ],
        [
            'code' => 'VIP20',
            'description' => '20% desconto exclusivo VIP.',
            'type' => 'PERCENTAGE',
            'value' => 20,
            'min_order_amount' => 100,
            'max_discount_amount' => 60,
            'per_user_limit' => 3,
            'is_active' => true,
            'is_public' => false, // secret coupon
            'expires_at_days' => 60,
        ],
        [
            'code' => 'FLASH50',
            'description' => 'R$50 off — Promoção relâmpago!',
            'type' => 'FIXED',
            'value' => 50,
            'min_order_amount' => 250,
            'max_discount_amount' => null,
            'usage_limit' => 100,
            'per_user_limit' => 1,
            'is_active' => true,
            'is_public' => true,
            'expires_at_days' => 3,
        ],
    ];

    public function run(): void
    {
        foreach ($this->coupons as $data) {
            $expiresIn = $data['expires_at_days'] ?? 30;
            unset($data['expires_at_days']);

            Coupon::firstOrCreate(
                ['code' => $data['code']],
                array_merge($data, [
                    'starts_at' => now(),
                    'expires_at' => now()->addDays($expiresIn),
                ])
            );
        }

        $this->command->info('✅ CouponSeeder: '.count($this->coupons).' coupons created.');
    }
}
