<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\CustomFormRequest;

class ResetPasswordRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Informe um e-mail válido.',
            'token.required' => 'O token é obrigatório.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
            'password_confirmation.required' => 'A confirmação de senha é obrigatória.',
        ];
    }
}
