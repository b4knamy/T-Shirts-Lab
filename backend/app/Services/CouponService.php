<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CouponService
{
    public function getPublicActive(): Collection
    {
        return Coupon::where('is_active', true)
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
    }

    /**
     * @return array{coupon: Coupon, discount: float}|string Returns data array on success or an error string.
     */
    public function validate(string $code, float $subtotal, User $user): array|string
    {
        $coupon = Coupon::where('code', strtoupper($code))->first();

        if (! $coupon) {
            return 'not_found';
        }

        if (! $coupon->isValid()) {
            return 'This coupon is no longer valid';
        }

        if ($coupon->hasUserReachedLimit($user->id)) {
            return 'You have already used this coupon the maximum number of times';
        }

        $discount = $coupon->calculateDiscount($subtotal);

        if ($discount <= 0) {
            return 'min_order:'.number_format((float) $coupon->min_order_amount, 2);
        }

        return compact('coupon', 'discount');
    }

    public function paginate(array $filters, int $page, int $limit): array
    {
        $query = Coupon::orderBy('created_at', 'desc');

        if (! empty($filters['search'])) {
            $query->where('code', 'ilike', '%'.$filters['search'].'%');
        }

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['status'])) {
            $status = $filters['status'];
            if ($status === 'active') {
                $query->where('is_active', true)
                    ->where(function ($q) {
                        $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'expired') {
                $query->where('expires_at', '<=', now());
            }
        }

        $total = $query->count();
        $coupons = $query->skip(($page - 1) * $limit)->take($limit)->get();

        return compact('coupons', 'total');
    }

    public function find(string $id): ?Coupon
    {
        return Coupon::find($id);
    }

    public function create(array $data): Coupon
    {
        $data['code'] = strtoupper($data['code']);

        return Coupon::create($data);
    }

    public function update(string $id, array $data): ?Coupon
    {
        $coupon = Coupon::find($id);

        if (! $coupon) {
            return null;
        }

        if (isset($data['code'])) {
            $data['code'] = strtoupper($data['code']);
        }

        $coupon->update($data);

        return $coupon->fresh();
    }

    public function delete(string $id): ?Coupon
    {
        $coupon = Coupon::find($id);

        if (! $coupon) {
            return null;
        }

        $coupon->delete();

        return $coupon;
    }
}
