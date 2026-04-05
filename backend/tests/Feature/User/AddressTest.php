<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    private function authAs(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    private function validAddressData(array $overrides = []): array
    {
        return array_merge([
            'label' => 'Casa',
            'street' => 'Rua das Flores',
            'number' => '123',
            'complement' => 'Apto 4B',
            'neighborhood' => 'Centro',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01000-000',
            'country' => 'BR',
            'is_default' => false,
        ], $overrides);
    }

    /* ══════════════════════════════════════════════════════════════ *
       *  GET /users/me/addresses
       * ══════════════════════════════════════════════════════════════ */

    public function test_list_addresses_returns_user_addresses(): void
    {
        $user = User::factory()->create();
        $user->addresses()->create($this->validAddressData(['label' => 'Casa']));
        $user->addresses()->create($this->validAddressData(['label' => 'Trabalho', 'street' => 'Av Paulista']));

        $token = $this->authAs($user);

        $response = $this->getJson('/api/v1/users/me/addresses', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertCount(2, $response->json('data'));
    }

    public function test_list_addresses_returns_empty_when_none(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $response = $this->getJson('/api/v1/users/me/addresses', [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk();
        $this->assertCount(0, $response->json('data'));
    }

    public function test_list_addresses_default_first(): void
    {
        $user = User::factory()->create();
        $user->addresses()->create($this->validAddressData(['label' => 'Backup', 'is_default' => false]));
        $user->addresses()->create($this->validAddressData(['label' => 'Main', 'is_default' => true]));

        $token = $this->authAs($user);

        $response = $this->getJson('/api/v1/users/me/addresses', [
            'Authorization' => "Bearer {$token}",
        ]);

        $items = $response->json('data');
        $this->assertEquals('Main', $items[0]['label']);
    }

    public function test_user_cannot_see_other_users_addresses(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1->addresses()->create($this->validAddressData(['label' => 'User1 Home']));
        $user2->addresses()->create($this->validAddressData(['label' => 'User2 Home']));

        $token = $this->authAs($user2);

        $response = $this->getJson('/api/v1/users/me/addresses', [
            'Authorization' => "Bearer {$token}",
        ]);

        $items = $response->json('data');
        $this->assertCount(1, $items);
        $this->assertEquals('User2 Home', $items[0]['label']);
    }

    /* ══════════════════════════════════════════════════════════════ *
       *  POST /users/me/addresses
       * ══════════════════════════════════════════════════════════════ */

    public function test_create_address(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $data = $this->validAddressData();

        $response = $this->postJson('/api/v1/users/me/addresses', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'street' => 'Rua das Flores',
                    'city' => 'São Paulo',
                ],
            ]);

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'street' => 'Rua das Flores',
        ]);
    }

    public function test_first_address_becomes_default(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $response = $this->postJson('/api/v1/users/me/addresses', $this->validAddressData([
            'is_default' => false,
        ]), [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(201);

        // Even though is_default=false was sent, first address should be default
        $this->assertTrue($user->addresses()->first()->is_default);
    }

    public function test_setting_new_default_unsets_previous(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        // Create first address (becomes default)
        $this->postJson('/api/v1/users/me/addresses', $this->validAddressData(['label' => 'First']), [
            'Authorization' => "Bearer {$token}",
        ]);

        // Create second address as default
        $this->postJson('/api/v1/users/me/addresses', $this->validAddressData([
            'label' => 'Second',
            'is_default' => true,
        ]), [
            'Authorization' => "Bearer {$token}",
        ]);

        $addresses = $user->addresses()->orderBy('created_at')->get();
        $this->assertFalse($addresses[0]->is_default);  // First should no longer be default
        $this->assertTrue($addresses[1]->is_default);    // Second should be default
    }

    /* ── Validation ──────────────────────────────────────────────── */

    public function test_create_address_fails_without_street(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $data = $this->validAddressData();
        unset($data['street']);

        $response = $this->postJson('/api/v1/users/me/addresses', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['street']);
    }

    public function test_create_address_fails_without_number(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $data = $this->validAddressData();
        unset($data['number']);

        $response = $this->postJson('/api/v1/users/me/addresses', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['number']);
    }

    public function test_create_address_fails_without_city(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $data = $this->validAddressData();
        unset($data['city']);

        $response = $this->postJson('/api/v1/users/me/addresses', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['city']);
    }

    public function test_create_address_fails_without_state(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $data = $this->validAddressData();
        unset($data['state']);

        $response = $this->postJson('/api/v1/users/me/addresses', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['state']);
    }

    public function test_create_address_fails_without_zip_code(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $data = $this->validAddressData();
        unset($data['zip_code']);

        $response = $this->postJson('/api/v1/users/me/addresses', $data, [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['zip_code']);
    }

    /* ══════════════════════════════════════════════════════════════ *
       *  PATCH /users/me/addresses/{id}
       * ══════════════════════════════════════════════════════════════ */

    public function test_update_address(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create($this->validAddressData());
        $token = $this->authAs($user);

        $response = $this->patchJson("/api/v1/users/me/addresses/{$address->id}", [
            'street' => 'Av Brasil',
            'number' => '999',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'street' => 'Av Brasil',
                    'number' => '999',
                ],
            ]);
    }

    public function test_update_address_set_default(): void
    {
        $user = User::factory()->create();
        $addr1 = $user->addresses()->create($this->validAddressData(['is_default' => true]));
        $addr2 = $user->addresses()->create($this->validAddressData(['is_default' => false]));

        $token = $this->authAs($user);

        $this->patchJson("/api/v1/users/me/addresses/{$addr2->id}", [
            'is_default' => true,
        ], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $addr1->refresh();
        $addr2->refresh();
        $this->assertFalse($addr1->is_default);
        $this->assertTrue($addr2->is_default);
    }

    public function test_update_address_not_found(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->patchJson("/api/v1/users/me/addresses/{$fakeId}", [
            'street' => 'Nowhere',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_update_another_users_address(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $address = $user1->addresses()->create($this->validAddressData());

        $token = $this->authAs($user2);

        $response = $this->patchJson("/api/v1/users/me/addresses/{$address->id}", [
            'street' => 'Hacked',
        ], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(404);

        // Original should be unchanged
        $address->refresh();
        $this->assertEquals('Rua das Flores', $address->street);
    }

    /* ══════════════════════════════════════════════════════════════ *
       *  DELETE /users/me/addresses/{id}
       * ══════════════════════════════════════════════════════════════ */

    public function test_delete_address(): void
    {
        $user = User::factory()->create();
        $address = $user->addresses()->create($this->validAddressData());
        $token = $this->authAs($user);

        $response = $this->deleteJson("/api/v1/users/me/addresses/{$address->id}", [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
    }

    public function test_delete_default_promotes_next(): void
    {
        $user = User::factory()->create();
        $addr1 = $user->addresses()->create($this->validAddressData(['label' => 'Default', 'is_default' => true]));
        $addr2 = $user->addresses()->create($this->validAddressData(['label' => 'Backup', 'is_default' => false]));

        $token = $this->authAs($user);

        $this->deleteJson("/api/v1/users/me/addresses/{$addr1->id}", [], [
            'Authorization' => "Bearer {$token}",
        ])->assertOk();

        $addr2->refresh();
        $this->assertTrue($addr2->is_default);
    }

    public function test_delete_address_not_found(): void
    {
        $user = User::factory()->create();
        $token = $this->authAs($user);

        $fakeId = '00000000-0000-0000-0000-000000000000';

        $response = $this->deleteJson("/api/v1/users/me/addresses/{$fakeId}", [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_delete_another_users_address(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $address = $user1->addresses()->create($this->validAddressData());
        $token = $this->authAs($user2);

        $response = $this->deleteJson("/api/v1/users/me/addresses/{$address->id}", [], [
            'Authorization' => "Bearer {$token}",
        ]);

        $response->assertStatus(404);

        // Should still exist
        $this->assertDatabaseHas('user_addresses', ['id' => $address->id]);
    }

    /* ── Auth ────────────────────────────────────────────────────── */

    public function test_all_address_endpoints_require_auth(): void
    {
        $this->getJson('/api/v1/users/me/addresses')->assertStatus(401);
        $this->postJson('/api/v1/users/me/addresses', $this->validAddressData())->assertStatus(401);
        $this->patchJson('/api/v1/users/me/addresses/fake-id', [])->assertStatus(401);
        $this->deleteJson('/api/v1/users/me/addresses/fake-id')->assertStatus(401);
    }
}
