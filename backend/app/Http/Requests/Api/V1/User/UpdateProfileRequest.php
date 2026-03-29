<?php

namespace App\Http\Requests\Api\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'firstName'         => 'sometimes|string|max:255',
      'lastName'          => 'sometimes|string|max:255',
      'phone'             => 'nullable|string|max:20',
      'profilePictureUrl' => 'nullable|string|url|max:500',
    ];
  }

  public function messages(): array
  {
    return [
      'firstName.string'          => 'O primeiro nome deve ser um texto.',
      'lastName.string'           => 'O sobrenome deve ser um texto.',
      'phone.max'                 => 'O telefone deve ter no máximo 20 caracteres.',
      'profilePictureUrl.url'     => 'A URL da foto de perfil deve ser válida.',
    ];
  }

  protected function failedValidation(Validator $validator): never
  {
    throw new HttpResponseException(
      response()->json([
        'success' => false,
        'message' => 'Validation error',
        'errors'  => $validator->errors(),
      ], 422)
    );
  }
}
