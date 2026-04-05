<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_intent_id' => 'required|string',
            'amount' => 'nullable|numeric|min:0.01',
            'reason' => 'nullable|string|in:duplicate,fraudulent,requested_by_customer',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_intent_id.required' => 'O ID do PaymentIntent é obrigatório.',
            'amount.numeric' => 'O valor do reembolso deve ser numérico.',
            'amount.min' => 'O valor mínimo para reembolso é R$ 0,01.',
            'reason.in' => 'Motivo inválido. Use: duplicate, fraudulent ou requested_by_customer.',
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
