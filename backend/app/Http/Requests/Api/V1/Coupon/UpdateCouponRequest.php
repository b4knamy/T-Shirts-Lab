<?php

namespace App\Http\Requests\Api\V1\Coupon;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UpdateCouponRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['sometimes', 'string', 'max:50', 'unique:coupons,code,' . $this->route('id')],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['sometimes', 'in:PERCENTAGE,FIXED'],
            'value' => ['sometimes', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_public' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.max' => 'O código deve ter no máximo 50 caracteres.',
            'code.unique' => 'Este código de cupom já está em uso.',
            'type.in' => 'O tipo deve ser PERCENTAGE ou FIXED.',
            'value.numeric' => 'O valor deve ser um número.',
            'value.min' => 'O valor deve ser maior que zero.',
            'min_order_amount.numeric' => 'O valor mínimo do pedido deve ser um número.',
            'max_discount_amount.numeric' => 'O desconto máximo deve ser um número.',
            'usage_limit.integer' => 'O limite de uso deve ser um número inteiro.',
            'per_user_limit.integer' => 'O limite por usuário deve ser um número inteiro.',
        ];
    }
}
