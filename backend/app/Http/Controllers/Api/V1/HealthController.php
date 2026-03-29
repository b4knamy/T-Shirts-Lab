<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
  use ApiResponse;

  public function check(): JsonResponse
  {
    $checks = [
      'app' => true,
      'database' => $this->checkDatabase(),
      'cache' => $this->checkCache(),
    ];

    $allHealthy = !in_array(false, $checks);

    return response()->json([
      'status' => $allHealthy ? 'healthy' : 'degraded',
      'timestamp' => now()->toISOString(),
      'checks' => $checks,
    ], $allHealthy ? 200 : 503);
  }

  private function checkDatabase(): bool
  {
    try {
      DB::connection()->getPdo();
      return true;
    } catch (\Exception $e) {
      return false;
    }
  }

  private function checkCache(): bool
  {
    try {
      Cache::put('health_check', true, 10);
      return Cache::get('health_check') === true;
    } catch (\Exception $e) {
      return false;
    }
  }
}
