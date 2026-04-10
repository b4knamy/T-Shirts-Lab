<?php

namespace App\Http\Requests\Api\V1\ProductImage;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UpdateProductImageRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_primary' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'alt_text.max' => 'O texto alternativo deve ter no máximo 255 caracteres.',
            'sort_order.integer' => 'A ordem deve ser um número inteiro.',
            'sort_order.min' => 'A ordem não pode ser negativa.',
            'is_primary.boolean' => 'O campo primário deve ser verdadeiro ou falso.',
        ];
    }
}
