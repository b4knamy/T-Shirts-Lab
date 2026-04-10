<?php

namespace App\Http\Requests\Api\V1\Order;

use App\Http\Requests\Api\V1\CustomFormRequest;

class StoreOrderRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.design_id' => ['nullable', 'uuid', 'exists:designs,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.customization_data' => ['nullable', 'array'],
            'shipping_address_id' => ['nullable', 'uuid', 'exists:user_addresses,id'],
            'billing_address_id' => ['nullable', 'uuid', 'exists:user_addresses,id'],
            'customer_notes' => ['nullable', 'string', 'max:1000'],
            'coupon_code' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Os itens do pedido são obrigatórios.',
            'items.array' => 'Os itens devem ser uma lista.',
            'items.min' => 'O pedido deve ter pelo menos 1 item.',
            'items.*.product_id.required' => 'O ID do produto é obrigatório.',
            'items.*.product_id.uuid' => 'O ID do produto é inválido.',
            'items.*.product_id.exists' => 'Produto não encontrado.',
            'items.*.design_id.uuid' => 'O ID do design é inválido.',
            'items.*.design_id.exists' => 'Design não encontrado.',
            'items.*.quantity.required' => 'A quantidade é obrigatória.',
            'items.*.quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'items.*.quantity.min' => 'A quantidade mínima é 1.',
            'items.*.quantity.max' => 'A quantidade máxima por item é 100.',
            'shipping_address_id.uuid' => 'O ID do endereço de entrega é inválido.',
            'shipping_address_id.exists' => 'Endereço de entrega não encontrado.',
            'billing_address_id.uuid' => 'O ID do endereço de cobrança é inválido.',
            'billing_address_id.exists' => 'Endereço de cobrança não encontrado.',
            'customer_notes.max' => 'As observações devem ter no máximo 1000 caracteres.',
            'coupon_code.max' => 'O código do cupom deve ter no máximo 50 caracteres.',
        ];
    }
}
