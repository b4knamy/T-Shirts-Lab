<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UploadAvatarRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'A imagem de avatar é obrigatória.',
            'avatar.image' => 'O arquivo deve ser uma imagem.',
            'avatar.mimes' => 'A imagem deve ser do tipo: jpg, jpeg, png ou webp.',
            'avatar.max' => 'A imagem deve ter no máximo 3MB.',
        ];
    }
}
