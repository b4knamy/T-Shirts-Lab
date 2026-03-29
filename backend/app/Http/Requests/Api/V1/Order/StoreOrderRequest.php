<?php

namespace App\Http\Requests\Api\V1\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'items'                    => 'required|array|min:1',
      'items.*.productId'        => 'required|uuid|exists:products,id',
      'items.*.designId'         => 'nullable|uuid|exists:designs,id',
      'items.*.quantity'         => 'required|integer|min:1|max:100',
      'items.*.customizationData' => 'nullable|array',
      'shippingAddressId'        => 'nullable|uuid|exists:user_addresses,id',
      'billingAddressId'         => 'nullable|uuid|exists:user_addresses,id',
      'customerNotes'            => 'nullable|string|max:1000',
    ];
  }

  public function messages(): array
  {
    return [
      'items.required'                => 'Os itens do pedido são obrigatórios.',
      'items.min'                     => 'O pedido deve ter pelo menos 1 item.',
      'items.*.productId.required'    => 'O ID do produto é obrigatório.',
      'items.*.productId.exists'      => 'Produto não encontrado.',
      'items.*.quantity.required'     => 'A quantidade é obrigatória.',
      'items.*.quantity.min'          => 'A quantidade mínima é 1.',
      'items.*.quantity.max'          => 'A quantidade máxima por item é 100.',
      'shippingAddressId.exists'      => 'Endereço de entrega não encontrado.',
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
