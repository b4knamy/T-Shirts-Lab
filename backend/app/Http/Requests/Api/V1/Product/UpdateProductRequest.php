<?php

namespace App\Http\Requests\Api\V1\Product;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UpdateProductRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'long_description' => ['nullable', 'string'],
            'category_id' => ['sometimes', 'uuid', 'exists:categories,id'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
            'is_featured' => ['boolean'],
            'status' => ['in:ACTIVE,INACTIVE,DRAFT,OUT_OF_STOCK'],
            'color' => ['nullable', 'string', 'max:50'],
            'size' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'O nome deve ter no máximo 255 caracteres.',
            'category_id.uuid' => 'O ID da categoria é inválido.',
            'category_id.exists' => 'A categoria informada não existe.',
            'price.numeric' => 'O preço deve ser um número.',
            'price.min' => 'O preço não pode ser negativo.',
            'stock_quantity.integer' => 'A quantidade deve ser um número inteiro.',
            'stock_quantity.min' => 'A quantidade não pode ser negativa.',
            'status.in' => 'O status deve ser ACTIVE, INACTIVE, DRAFT ou OUT_OF_STOCK.',
            'discount_percent.max' => 'O percentual de desconto não pode ultrapassar 100%.',
        ];
    }
}
