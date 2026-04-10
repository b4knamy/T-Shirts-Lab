<?php

namespace App\Http\Requests\Api\V1\Coupon;

use App\Http\Requests\Api\V1\CustomFormRequest;

class StoreCouponRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'description' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:PERCENTAGE,FIXED'],
            'value' => ['required', 'numeric', 'min:0.01'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['boolean'],
            'is_public' => ['boolean'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'O código do cupom é obrigatório.',
            'code.max' => 'O código deve ter no máximo 50 caracteres.',
            'code.unique' => 'Este código de cupom já está em uso.',
            'type.required' => 'O tipo do cupom é obrigatório.',
            'type.in' => 'O tipo deve ser PERCENTAGE ou FIXED.',
            'value.required' => 'O valor do desconto é obrigatório.',
            'value.numeric' => 'O valor deve ser um número.',
            'value.min' => 'O valor deve ser maior que zero.',
            'min_order_amount.numeric' => 'O valor mínimo do pedido deve ser um número.',
            'max_discount_amount.numeric' => 'O desconto máximo deve ser um número.',
            'usage_limit.integer' => 'O limite de uso deve ser um número inteiro.',
            'per_user_limit.integer' => 'O limite por usuário deve ser um número inteiro.',
            'expires_at.after_or_equal' => 'A data de expiração deve ser igual ou posterior à data de início.',
        ];
    }
}
