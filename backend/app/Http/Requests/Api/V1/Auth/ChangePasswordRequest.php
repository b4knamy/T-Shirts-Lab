<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\CustomFormRequest;

class ChangePasswordRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'A senha atual é obrigatoria.',
            'password.required' => 'A nova senha é obrigatoria.',
            'password.min' => 'A senha deve ter no minimo 8 caracteres.',
            'password.confirmed' => 'As senhas nao coincidem.',
            'password_confirmation.required' => 'A confirmacao de senha e obrigatoria.',
        ];
    }
}
