<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UpdateProfileRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'profile_picture_url' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.string' => 'O primeiro nome deve ser um texto.',
            'first_name.max' => 'O primeiro nome deve ter no máximo 255 caracteres.',
            'last_name.string' => 'O sobrenome deve ser um texto.',
            'last_name.max' => 'O sobrenome deve ter no máximo 255 caracteres.',
            'phone.max' => 'O telefone deve ter no máximo 20 caracteres.',
            'profile_picture_url.max' => 'A URL da foto de perfil deve ter no máximo 500 caracteres.',
        ];
    }
}
