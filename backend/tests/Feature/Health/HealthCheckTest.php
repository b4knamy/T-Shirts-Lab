<?php

namespace Tests\Feature\Health;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
  use RefreshDatabase;

  private string $endpoint = '/api/v1/health';

  /* ── Success ─────────────────────────────────────────────────── */

  public function test_health_returns_healthy(): void
  {
    $response = $this->getJson($this->endpoint);

    $response->assertOk()
      ->assertJsonStructure([
        'status',
        'timestamp',
        'checks' => ['app', 'database', 'cache'],
      ])
      ->assertJson([
        'status' => 'healthy',
        'checks' => [
          'app'      => true,
          'database' => true,
          'cache'    => true,
        ],
      ]);
  }

  public function test_health_returns_timestamp(): void
  {
    $response = $this->getJson($this->endpoint);

    $response->assertOk();
    $this->assertNotNull($response->json('timestamp'));
  }

  public function test_health_is_publicly_accessible(): void
  {
    // No auth needed
    $response = $this->getJson($this->endpoint);

    $response->assertOk();
  }
}
