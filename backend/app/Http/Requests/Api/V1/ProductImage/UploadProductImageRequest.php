<?php

namespace App\Http\Requests\Api\V1\ProductImage;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UploadProductImageRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'is_primary' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'A imagem é obrigatória.',
            'image.image' => 'O arquivo enviado deve ser uma imagem.',
            'image.mimes' => 'A imagem deve ser do tipo: jpg, jpeg, png ou webp.',
            'image.max' => 'A imagem deve ter no máximo 5MB.',
            'alt_text.max' => 'O texto alternativo deve ter no máximo 255 caracteres.',
            'is_primary.boolean' => 'O campo primário deve ser verdadeiro ou falso.',
        ];
    }
}
