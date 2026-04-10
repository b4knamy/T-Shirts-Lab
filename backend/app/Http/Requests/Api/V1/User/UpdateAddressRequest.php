<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UpdateAddressRequest extends CustomFormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'label' => ['nullable', 'string', 'max:100'],
      'street' => ['sometimes', 'string', 'max:255'],
      'number' => ['sometimes', 'string', 'max:20'],
      'complement' => ['nullable', 'string', 'max:255'],
      'neighborhood' => ['nullable', 'string', 'max:255'],
      'city' => ['sometimes', 'string', 'max:255'],
      'state' => ['sometimes', 'string', 'max:2'],
      'zip_code' => ['sometimes', 'string', 'max:20'],
      'country' => ['nullable', 'string', 'max:2'],
      'is_default' => ['boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'street.string' => 'O rua deve ser um texto.',
      'number.string' => 'O número deve ser um texto.',
    ];
  }
}
