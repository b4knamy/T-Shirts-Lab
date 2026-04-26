<?php

namespace App\Http\Requests\Api\V1\User;

use App\Http\Requests\Api\V1\CustomFormRequest;

class DeleteAccountRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Current password is required.',
        ];
    }
}
