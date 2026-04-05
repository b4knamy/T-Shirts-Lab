<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreatePaymentIntentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'required|uuid|exists:orders,id',
            'currency' => 'nullable|string|size:3',
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.required' => 'O ID do pedido é obrigatório.',
            'order_id.exists' => 'Pedido não encontrado.',
            'currency.size' => 'A moeda deve ter exatamente 3 caracteres (ex: brl, usd).',
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
