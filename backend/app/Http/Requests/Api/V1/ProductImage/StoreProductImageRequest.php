<?php

namespace App\Http\Requests\Api\V1\ProductImage;

use App\Http\Requests\Api\V1\CustomFormRequest;

class StoreProductImageRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image_url' => ['required', 'string', 'max:1000'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_primary' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'image_url.required' => 'A URL da imagem é obrigatória.',
            'image_url.max' => 'A URL da imagem deve ter no máximo 1000 caracteres.',
            'alt_text.max' => 'O texto alternativo deve ter no máximo 255 caracteres.',
            'sort_order.integer' => 'A ordem deve ser um número inteiro.',
            'sort_order.min' => 'A ordem não pode ser negativa.',
            'is_primary.boolean' => 'O campo primário deve ser verdadeiro ou falso.',
        ];
    }
}
