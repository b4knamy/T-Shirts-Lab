<?php

namespace App\Http\Requests\Api\V1\Category;

use App\Http\Requests\Api\V1\CustomFormRequest;

class CategoryUpdateRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'image_url' => ['nullable', 'string', 'url', 'max:500'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'O nome da categoria deve ter no máximo 100 caracteres.',
            'description.max' => 'A descrição deve ter no máximo 500 caracteres.',
            'image_url.url' => 'Informe uma URL de imagem válida.',
            'image_url.max' => 'A URL da imagem deve ter no máximo 500 caracteres.',
            'is_active.boolean' => 'O campo ativo deve ser verdadeiro ou falso.',
        ];
    }
}
