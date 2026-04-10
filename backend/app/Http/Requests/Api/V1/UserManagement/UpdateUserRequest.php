<?php

namespace App\Http\Requests\Api\V1\UserManagement;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UpdateUserRequest extends CustomFormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'role' => ['sometimes', 'in:CUSTOMER,MODERATOR,ADMIN'],
      'is_active' => ['sometimes', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'role.in' => 'Role inválido.',
      'is_active.boolean' => 'is_active deve ser booleano.',
    ];
  }
}
