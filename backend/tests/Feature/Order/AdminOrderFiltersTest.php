<?php

namespace Tests\Feature\Order;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminOrderFiltersTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/orders';

    private function authAdmin(): array
    {
        $admin = User::factory()->admin()->create([
            'password_hash' => Hash::make('Secret@123'),
        ]);
        $token = auth('api')->login($admin);

        return ['Authorization' => "Bearer $token"];
    }

    /* ── Filter by status ────────────────────────────────────────── */

    public function test_filter_orders_by_status_pending(): void
    {
        Order::factory()->pending()->count(3)->create();
        Order::factory()->paid()->count(2)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=PENDING', $headers);

        $response->assertOk();
        $this->assertEquals(3, $response->json('data.total'));
    }

    public function test_filter_orders_by_status_confirmed(): void
    {
        Order::factory()->pending()->count(2)->create();
        Order::factory()->paid()->count(4)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=CONFIRMED', $headers);

        $response->assertOk();
        $this->assertEquals(4, $response->json('data.total'));
    }

    public function test_filter_orders_by_status_cancelled(): void
    {
        Order::factory()->cancelled()->count(2)->create();
        Order::factory()->pending()->count(3)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=CANCELLED', $headers);

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.total'));
    }

    public function test_filter_orders_by_status_returns_empty_when_none_match(): void
    {
        Order::factory()->pending()->count(3)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=SHIPPED', $headers);

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.total'));
    }

    /* ── Filter by payment_status ────────────────────────────────── */

    public function test_filter_orders_by_payment_status_completed(): void
    {
        $completedOrder = Order::factory()->create();
        Payment::factory()->create(['order_id' => $completedOrder->id, 'status' => 'COMPLETED']);

        $pendingOrder = Order::factory()->create();
        Payment::factory()->create(['order_id' => $pendingOrder->id, 'status' => 'PENDING']);

        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?payment_status=COMPLETED', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    public function test_filter_orders_by_payment_status_pending(): void
    {
        $order1 = Order::factory()->create();
        Payment::factory()->create(['order_id' => $order1->id, 'status' => 'PENDING']);

        $order2 = Order::factory()->create();
        Payment::factory()->create(['order_id' => $order2->id, 'status' => 'PENDING']);

        $order3 = Order::factory()->create();
        Payment::factory()->create(['order_id' => $order3->id, 'status' => 'COMPLETED']);

        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?payment_status=PENDING', $headers);

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.total'));
    }

    public function test_filter_orders_by_payment_status_failed(): void
    {
        $order1 = Order::factory()->create();
        Payment::factory()->create(['order_id' => $order1->id, 'status' => 'FAILED']);

        $order2 = Order::factory()->create();
        Payment::factory()->create(['order_id' => $order2->id, 'status' => 'COMPLETED']);

        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?payment_status=FAILED', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    /* ── Search by order number ──────────────────────────────────── */

    public function test_search_orders_by_order_number(): void
    {
        Order::factory()->create(['order_number' => 'ORD-ABCD-1234']);
        Order::factory()->create(['order_number' => 'ORD-WXYZ-5678']);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=ABCD', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
        $this->assertEquals('ORD-ABCD-1234', $response->json('data.data.0.order_number'));
    }

    public function test_search_orders_is_case_insensitive(): void
    {
        Order::factory()->create(['order_number' => 'ORD-ABCD-1234']);
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=abcd', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    public function test_search_orders_by_customer_name(): void
    {
        $user = User::factory()->create(['first_name' => 'Carlos', 'last_name' => 'Silva']);
        Order::factory()->create(['user_id' => $user->id]);
        Order::factory()->count(3)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=Carlos', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    public function test_search_orders_by_customer_email(): void
    {
        $user = User::factory()->create(['email' => 'unique-buyer@example.com']);
        Order::factory()->create(['user_id' => $user->id]);
        Order::factory()->count(2)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=unique-buyer', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    public function test_search_orders_returns_empty_when_no_match(): void
    {
        Order::factory()->count(3)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?search=NONEXISTENT-999', $headers);

        $response->assertOk();
        $this->assertEquals(0, $response->json('data.total'));
    }

    /* ── Combined filters ────────────────────────────────────────── */

    public function test_combine_status_and_search_filters(): void
    {
        $user = User::factory()->create(['first_name' => 'Maria']);
        Order::factory()->pending()->create(['user_id' => $user->id]);
        Order::factory()->paid()->create(['user_id' => $user->id]);
        Order::factory()->pending()->count(2)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=PENDING&search=Maria', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    public function test_combine_status_and_payment_status_filters(): void
    {
        $order1 = Order::factory()->pending()->create();
        Payment::factory()->create(['order_id' => $order1->id, 'status' => 'PENDING']);

        $order2 = Order::factory()->pending()->create();
        Payment::factory()->create(['order_id' => $order2->id, 'status' => 'COMPLETED']);

        $order3 = Order::factory()->paid()->create();
        Payment::factory()->create(['order_id' => $order3->id, 'status' => 'COMPLETED']);

        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=PENDING&payment_status=PENDING', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    public function test_combine_all_three_filters(): void
    {
        $user = User::factory()->create(['first_name' => 'Ana']);

        $order = Order::factory()->pending()->create([
            'user_id' => $user->id,
            'order_number' => 'ORD-ANA-0001',
        ]);
        Payment::factory()->create(['order_id' => $order->id, 'status' => 'PENDING']);

        // Noise orders
        Order::factory()->pending()->count(3)->create();
        Order::factory()->paid()->count(2)->create();

        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint.'?status=PENDING&payment_status=PENDING&search=Ana', $headers);

        $response->assertOk();
        $this->assertEquals(1, $response->json('data.total'));
    }

    /* ── Without filters returns all ─────────────────────────────── */

    public function test_no_filters_returns_all_orders(): void
    {
        Order::factory()->pending()->count(2)->create();
        Order::factory()->paid()->count(3)->create();
        $headers = $this->authAdmin();

        $response = $this->getJson($this->endpoint, $headers);

        $response->assertOk();
        $this->assertEquals(5, $response->json('data.total'));
    }
}
