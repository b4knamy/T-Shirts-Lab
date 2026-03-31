<?php

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RefreshTokenRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'refresh_token' => 'required|string',
    ];
  }

  public function messages(): array
  {
    return [
      'refresh_token.required' => 'O refresh token é obrigatório.',
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
