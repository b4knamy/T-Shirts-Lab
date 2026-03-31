<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Coupon\StoreCouponRequest;
use App\Http\Requests\Api\V1\Coupon\UpdateCouponRequest;
use App\Http\Requests\Api\V1\Coupon\ValidateCouponRequest;
use App\Http\Resources\Api\V1\CouponResource;
use App\Models\Coupon;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CouponController extends Controller
{
  use ApiResponse;

  /* ── Public: active promo banners (is_public + valid) ────────────── */
  public function publicActive(): JsonResponse
  {
    $coupons = Coupon::where('is_active', true)
      ->where('is_public', true)
      ->where(function ($q) {
        $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
      })
      ->where(function ($q) {
        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
      })
      ->where(function ($q) {
        $q->whereNull('usage_limit')
          ->orWhereRaw('usage_count < usage_limit');
      })
      ->orderBy('expires_at')
      ->get();

    return $this->success(CouponResource::collection($coupons));
  }

  /* ── Authenticated: validate a coupon code ──────────────────────── */
  public function validate(ValidateCouponRequest $request): JsonResponse
  {
    $user   = JWTAuth::parseToken()->authenticate();
    $coupon = Coupon::where('code', strtoupper($request->validated('code')))->first();

    if (!$coupon) {
      return $this->error('Coupon not found', 404);
    }

    if (!$coupon->isValid()) {
      return $this->error('This coupon is no longer valid', 422);
    }

    if ($coupon->hasUserReachedLimit($user->id)) {
      return $this->error('You have already used this coupon the maximum number of times', 422);
    }

    $discount = $coupon->calculateDiscount((float) $request->validated('subtotal'));

    if ($discount <= 0) {
      return $this->error(
        'Minimum order amount of $' . number_format((float) $coupon->min_order_amount, 2) . ' required',
        422
      );
    }

    return $this->success([
      'coupon'   => new CouponResource($coupon),
      'discount' => $discount,
    ]);
  }

  /* ── Admin CRUD ─────────────────────────────────────────────────── */

  public function index(Request $request): JsonResponse
  {
    $page  = (int) $request->get('page', 1);
    $limit = min((int) $request->get('limit', 20), 100);

    $query = Coupon::orderBy('created_at', 'desc');

    if ($request->has('search')) {
      $query->where('code', 'ilike', '%' . $request->get('search') . '%');
    }

    $total   = $query->count();
    $coupons = $query->skip(($page - 1) * $limit)->take($limit)->get();

    return $this->paginated(CouponResource::collection($coupons), $total, $page, $limit);
  }

  public function show(string $id): JsonResponse
  {
    $coupon = Coupon::find($id);

    if (!$coupon) {
      return $this->error('Coupon not found', 404);
    }

    return $this->success(new CouponResource($coupon));
  }

  public function store(StoreCouponRequest $request): JsonResponse
  {
    $data = $request->validated();
    $data['code'] = strtoupper($data['code']);

    $coupon = Coupon::create($data);

    return $this->success(new CouponResource($coupon), 'Coupon created', 201);
  }

  public function update(UpdateCouponRequest $request, string $id): JsonResponse
  {
    $coupon = Coupon::find($id);

    if (!$coupon) {
      return $this->error('Coupon not found', 404);
    }

    $data = $request->validated();
    if (isset($data['code'])) {
      $data['code'] = strtoupper($data['code']);
    }

    $coupon->update($data);

    return $this->success(new CouponResource($coupon->fresh()), 'Coupon updated');
  }

  public function destroy(string $id): JsonResponse
  {
    $coupon = Coupon::find($id);

    if (!$coupon) {
      return $this->error('Coupon not found', 404);
    }

    $coupon->delete();

    return $this->success(null, 'Coupon deleted');
  }
}
