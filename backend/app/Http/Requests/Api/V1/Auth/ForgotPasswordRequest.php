<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Http\Requests\Api\V1\CustomFormRequest;

class ForgotPasswordRequest extends CustomFormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'email' => 'required|email',
    ];
  }

  public function messages(): array
  {
    return [
      'email.required' => 'O e-mail é obrigatório.',
      'email.email'    => 'Informe um e-mail válido.',
    ];
  }
}
