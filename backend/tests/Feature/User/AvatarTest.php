<?php

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

class AvatarTest extends TestCase
{
  use RefreshDatabase;

  private string $endpoint = '/api/v1/users/me/avatar';

  private function authAs(User $user): string
  {
    return JWTAuth::fromUser($user);
  }

  protected function setUp(): void
  {
    parent::setUp();
    Storage::fake('public');
  }

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_upload_avatar_jpg(): void
  {
    $user  = User::factory()->create();
    $token = $this->authAs($user);

    $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

    $response = $this->postJson($this->endpoint, [
      'avatar' => $file,
    ], [
      'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()
      ->assertJson(['success' => true]);

    // profile_picture_url should be set
    $user->refresh();
    $this->assertNotNull($user->profile_picture_url);
    $this->assertStringContainsString('avatars/', $user->profile_picture_url);
  }

  public function test_upload_avatar_png(): void
  {
    $user  = User::factory()->create();
    $token = $this->authAs($user);

    $file = UploadedFile::fake()->image('avatar.png', 200, 200);

    $response = $this->postJson($this->endpoint, [
      'avatar' => $file,
    ], [
      'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk();
  }

  public function test_upload_avatar_webp(): void
  {
    $user  = User::factory()->create();
    $token = $this->authAs($user);

    $file = UploadedFile::fake()->image('avatar.webp', 200, 200);

    $response = $this->postJson($this->endpoint, [
      'avatar' => $file,
    ], [
      'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk();
  }

  public function test_upload_avatar_replaces_old_one(): void
  {
    $user  = User::factory()->create();
    $token = $this->authAs($user);

    // Upload first avatar
    $first = UploadedFile::fake()->image('first.jpg', 200, 200);
    $this->postJson($this->endpoint, ['avatar' => $first], [
      'Authorization' => "Bearer {$token}",
    ])->assertOk();

    $user->refresh();
    $firstUrl = $user->profile_picture_url;

    // Upload second avatar
    $second = UploadedFile::fake()->image('second.jpg', 200, 200);
    $this->postJson($this->endpoint, ['avatar' => $second], [
      'Authorization' => "Bearer {$token}",
    ])->assertOk();

    $user->refresh();
    $secondUrl = $user->profile_picture_url;

    $this->assertNotEquals($firstUrl, $secondUrl);
  }

  /* ── Validation ──────────────────────────────────────────────── */

  public function test_upload_fails_without_file(): void
  {
    $user  = User::factory()->create();
    $token = $this->authAs($user);

    $response = $this->postJson($this->endpoint, [], [
      'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['avatar']);
  }

  public function test_upload_fails_with_non_image_file(): void
  {
    $user  = User::factory()->create();
    $token = $this->authAs($user);

    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    $response = $this->postJson($this->endpoint, [
      'avatar' => $file,
    ], [
      'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['avatar']);
  }

  public function test_upload_fails_with_oversized_file(): void
  {
    $user  = User::factory()->create();
    $token = $this->authAs($user);

    // Max is 3072 KB = 3 MB
    $file = UploadedFile::fake()->image('big.jpg')->size(4000);

    $response = $this->postJson($this->endpoint, [
      'avatar' => $file,
    ], [
      'Authorization' => "Bearer {$token}",
    ]);

    $response->assertStatus(422)
      ->assertJsonValidationErrors(['avatar']);
  }

  public function test_upload_fails_with_gif(): void
  {
    $user  = User::factory()->create();
    $token = $this->authAs($user);

    $file = UploadedFile::fake()->image('anim.gif', 100, 100);

    $response = $this->postJson($this->endpoint, [
      'avatar' => $file,
    ], [
      'Authorization' => "Bearer {$token}",
    ]);

    // GIF is not in the allowed mimes (jpg,jpeg,png,webp)
    $response->assertStatus(422)
      ->assertJsonValidationErrors(['avatar']);
  }

  /* ── Auth ────────────────────────────────────────────────────── */

  public function test_upload_fails_without_auth(): void
  {
    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->postJson($this->endpoint, [
      'avatar' => $file,
    ]);

    $response->assertStatus(401);
  }
}
