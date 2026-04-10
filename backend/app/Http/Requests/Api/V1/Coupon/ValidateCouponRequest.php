<?php

namespace App\Http\Requests\Api\V1\Coupon;

use App\Http\Requests\Api\V1\CustomFormRequest;

class ValidateCouponRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'subtotal' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'O código do cupom é obrigatório.',
            'subtotal.required' => 'O subtotal é obrigatório.',
            'subtotal.numeric' => 'O subtotal deve ser um número.',
            'subtotal.min' => 'O subtotal não pode ser negativo.',
        ];
    }
}
