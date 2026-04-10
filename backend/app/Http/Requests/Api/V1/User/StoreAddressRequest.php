<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\CustomFormRequest;

class StoreAddressRequest extends CustomFormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'label' => ['nullable', 'string', 'max:100'],
      'street' => ['required', 'string', 'max:255'],
      'number' => ['required', 'string', 'max:20'],
      'complement' => ['nullable', 'string', 'max:255'],
      'neighborhood' => ['nullable', 'string', 'max:255'],
      'city' => ['required', 'string', 'max:255'],
      'state' => ['required', 'string', 'max:2'],
      'zip_code' => ['required', 'string', 'max:20'],
      'country' => ['nullable', 'string', 'max:2'],
      'is_default' => ['boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'street.required' => 'O rua é obrigatória.',
      'number.required' => 'O número é obrigatório.',
      'city.required' => 'A cidade é obrigatória.',
      'state.required' => 'O estado é obrigatório.',
      'zip_code.required' => 'O CEP é obrigatório.',
    ];
  }
}
