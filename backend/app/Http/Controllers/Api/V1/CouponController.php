<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Coupon\StoreCouponRequest;
use App\Http\Requests\Api\V1\Coupon\UpdateCouponRequest;
use App\Http\Requests\Api\V1\Coupon\ValidateCouponRequest;
use App\Http\Resources\Api\V1\CouponResource;
use App\Services\CouponService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class CouponController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly CouponService $couponService
    ) {}

    /* ── Public: active promo banners (is_public + valid) ────────────── */
    public function publicActive(): JsonResponse
    {
        $coupons = $this->couponService->getPublicActive();

        return $this->success(CouponResource::collection($coupons));
    }

    /* ── Authenticated: validate a coupon code ──────────────────────── */
    public function validate(ValidateCouponRequest $request): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();
        $result = $this->couponService->validate(
            $request->validated('code'),
            (float) $request->validated('subtotal'),
            $user
        );

        if ($result === 'not_found') {
            return $this->error('Coupon not found', 404);
        }

        if (is_string($result)) {
            if (str_starts_with($result, 'min_order:')) {
                $amount = substr($result, strlen('min_order:'));

                return $this->error('Minimum order amount of $' . $amount . ' required', 422);
            }

            return $this->error($result, 422);
        }

        return $this->success([
            'coupon' => new CouponResource($result['coupon']),
            'discount' => $result['discount'],
        ]);
    }

    /* ── Admin CRUD ─────────────────────────────────────────────────── */

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $limit = min((int) $request->get('limit', 20), 100);

        $filters = $request->only(['search', 'type', 'status']);
        $result = $this->couponService->paginate($filters, $page, $limit);

        return $this->paginated(CouponResource::collection($result['coupons']), $result['total'], $page, $limit);
    }

    public function show(string $id): JsonResponse
    {
        $coupon = $this->couponService->find($id);

        if (! $coupon) {
            return $this->error('Coupon not found', 404);
        }

        return $this->success(new CouponResource($coupon));
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = $this->couponService->create($request->validated());

        return $this->success(new CouponResource($coupon), 'Coupon created', 201);
    }

    public function update(UpdateCouponRequest $request, string $id): JsonResponse
    {
        $coupon = $this->couponService->update($id, $request->validated());

        if (! $coupon) {
            return $this->error('Coupon not found', 404);
        }

        return $this->success(new CouponResource($coupon), 'Coupon updated');
    }

    public function destroy(string $id): JsonResponse
    {
        $coupon = $this->couponService->delete($id);

        if (! $coupon) {
            return $this->error('Coupon not found', 404);
        }

        return $this->success(null, 'Coupon deleted');
    }
}
