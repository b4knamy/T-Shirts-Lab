<?php

namespace App\Http\Requests\Api\V1\UserManagement;

use App\Http\Requests\Api\V1\CustomFormRequest;

class StoreStaffRequest extends CustomFormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'email' => ['required', 'email', 'unique:users,email'],
      'password' => ['required', 'string', 'min:8'],
      'first_name' => ['required', 'string', 'max:100'],
      'last_name' => ['required', 'string', 'max:100'],
      'phone' => ['nullable', 'string', 'max:20'],
      'role' => ['required', 'in:MODERATOR,ADMIN'],
    ];
  }

  public function messages(): array
  {
    return [
      'email.required' => 'O e-mail é obrigatório.',
      'email.email' => 'Informe um e-mail válido.',
      'email.unique' => 'Este e-mail já está cadastrado.',
      'password.required' => 'A senha é obrigatória.',
    ];
  }
}
