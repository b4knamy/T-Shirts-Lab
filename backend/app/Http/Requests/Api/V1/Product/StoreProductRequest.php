<?php

namespace App\Http\Requests\Api\V1\Product;

use App\Http\Requests\Api\V1\CustomFormRequest;

class StoreProductRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'long_description' => ['nullable', 'string'],
            'category_id' => ['required', 'uuid', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'unique:products,sku'],
            'is_featured' => ['boolean'],
            'status' => ['in:ACTIVE,INACTIVE,DRAFT'],
            'color' => ['nullable', 'string', 'max:50'],
            'size' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do produto é obrigatório.',
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'description.required' => 'A descrição é obrigatória.',
            'category_id.required' => 'A categoria é obrigatória.',
            'category_id.uuid' => 'O ID da categoria é inválido.',
            'category_id.exists' => 'A categoria informada não existe.',
            'price.required' => 'O preço é obrigatório.',
            'price.numeric' => 'O preço deve ser um número.',
            'price.min' => 'O preço não pode ser negativo.',
            'stock_quantity.required' => 'A quantidade em estoque é obrigatória.',
            'stock_quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'stock_quantity.min' => 'A quantidade não pode ser negativa.',
            'sku.unique' => 'Este SKU já está em uso.',
            'status.in' => 'O status deve ser ACTIVE, INACTIVE ou DRAFT.',
            'discount_percent.max' => 'O percentual de desconto não pode ultrapassar 100%.',
        ];
    }
}
