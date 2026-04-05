<?php

namespace App\Http\Requests\Api\V1\Payment;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ConfirmPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payment_intent_id' => 'required|string',
            'payment_method_id' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'payment_intent_id.required' => 'O ID do PaymentIntent é obrigatório.',
            'payment_method_id.required' => 'O ID do método de pagamento é obrigatório.',
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
