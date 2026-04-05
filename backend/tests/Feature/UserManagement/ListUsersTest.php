<?php

namespace Tests\Feature\UserManagement;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class ListUsersTest extends TestCase
{
    use RefreshDatabase;

    private string $endpoint = '/api/v1/users';

    private function authAs(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    /* ── Access control ──────────────────────────────────────────── */

    public function test_super_admin_can_list_users(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->count(5)->create();

        $token = $this->authAs($admin);

        $response = $this->getJson($this->endpoint, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'meta' => ['total', 'page', 'limit', 'total_pages'],
                ],
            ]);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        $token = $this->authAs($admin);

        $response = $this->getJson($this->endpoint, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();
    }

    public function test_moderator_can_list_users(): void
    {
        $mod = User::factory()->moderator()->create();
        $token = $this->authAs($mod);

        $response = $this->getJson($this->endpoint, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();
    }

    public function test_customer_cannot_list_users(): void
    {
        $customer = User::factory()->create();
        $token = $this->authAs($customer);

        $response = $this->getJson($this->endpoint, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(403);
    }

    public function test_unauthenticated_cannot_list_users(): void
    {
        $response = $this->getJson($this->endpoint);

        $response->assertStatus(401);
    }

    /* ── Filtering ───────────────────────────────────────────────── */

    public function test_filter_by_role(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->count(3)->create(['role' => 'CUSTOMER']);
        User::factory()->count(2)->admin()->create();

        $token = $this->authAs($admin);

        $response = $this->getJson("{$this->endpoint}?role=ADMIN", [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();

        $users = $response->json('data.data');
        foreach ($users as $user) {
            $this->assertEquals('ADMIN', $user['role']);
        }
    }

    public function test_search_by_name(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->create(['first_name' => 'Unique_Name_XYZ']);
        User::factory()->count(3)->create();

        $token = $this->authAs($admin);

        $response = $this->getJson("{$this->endpoint}?search=Unique_Name_XYZ", [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('Unique_Name_XYZ', $data[0]['first_name']);
    }

    public function test_search_by_email(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->create(['email' => 'findme_xyz@example.com']);
        User::factory()->count(3)->create();

        $token = $this->authAs($admin);

        $response = $this->getJson("{$this->endpoint}?search=findme_xyz", [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();

        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('findme_xyz@example.com', $data[0]['email']);
    }

    /* ── Pagination ──────────────────────────────────────────────── */

    public function test_pagination_with_custom_limit(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->count(20)->create();

        $token = $this->authAs($admin);

        $response = $this->getJson("{$this->endpoint}?limit=5", [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();

        $meta = $response->json('data.meta');
        $this->assertEquals(5, $meta['limit']);
        $this->assertEquals(21, $meta['total']); // 20 + admin
        $this->assertCount(5, $response->json('data.data'));
    }

    public function test_pagination_default_limit(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->count(20)->create();

        $token = $this->authAs($admin);

        $response = $this->getJson($this->endpoint, [
            'Authorization' => "Bearer {$token}",
        ]);

        $meta = $response->json('data.meta');
        $this->assertEquals(15, $meta['limit']); // default is 15
    }
}
