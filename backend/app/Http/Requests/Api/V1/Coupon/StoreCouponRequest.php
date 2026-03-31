<?php

namespace App\Http\Requests\Api\V1\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'code'                => 'required|string|max:50|unique:coupons,code',
      'description'         => 'nullable|string|max:255',
      'type'                => 'required|in:PERCENTAGE,FIXED',
      'value'               => 'required|numeric|min:0.01',
      'min_order_amount'    => 'nullable|numeric|min:0',
      'max_discount_amount' => 'nullable|numeric|min:0',
      'usage_limit'         => 'nullable|integer|min:1',
      'per_user_limit'      => 'nullable|integer|min:1',
      'is_active'           => 'boolean',
      'is_public'           => 'boolean',
      'starts_at'           => 'nullable|date',
      'expires_at'          => 'nullable|date|after_or_equal:starts_at',
    ];
  }
}
