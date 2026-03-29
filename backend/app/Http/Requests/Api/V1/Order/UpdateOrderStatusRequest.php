<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateOrderStatusRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'status'     => 'required|in:PENDING,CONFIRMED,PROCESSING,SHIPPED,DELIVERED,CANCELLED,REFUNDED',
      'adminNotes' => 'nullable|string|max:1000',
    ];
  }

  public function messages(): array
  {
    return [
      'status.required' => 'O status é obrigatório.',
      'status.in'       => 'Status inválido. Use: PENDING, CONFIRMED, PROCESSING, SHIPPED, DELIVERED, CANCELLED ou REFUNDED.',
    ];
  }

  protected function failedValidation(Validator $validator): never
  {
    throw new HttpResponseException(
      response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422)
    );
  }
}
