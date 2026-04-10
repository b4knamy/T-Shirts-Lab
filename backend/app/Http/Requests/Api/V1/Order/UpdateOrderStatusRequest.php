<?php

namespace App\Http\Requests\Api\V1\Order;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UpdateOrderStatusRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:PENDING,CONFIRMED,PROCESSING,SHIPPED,DELIVERED,CANCELLED,REFUNDED'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'O status é obrigatório.',
            'status.in' => 'Status inválido. Use: PENDING, CONFIRMED, PROCESSING, SHIPPED, DELIVERED, CANCELLED ou REFUNDED.',
            'admin_notes.max' => 'As notas administrativas devem ter no máximo 1000 caracteres.',
        ];
    }
}
