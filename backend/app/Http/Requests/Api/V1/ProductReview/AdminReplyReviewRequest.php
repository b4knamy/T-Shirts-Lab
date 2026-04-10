<?php

namespace App\Http\Requests\Api\V1\ProductReview;

use App\Http\Requests\Api\V1\CustomFormRequest;

class AdminReplyReviewRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'admin_reply' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_reply.required' => 'A resposta do administrador é obrigatória.',
            'admin_reply.max' => 'A resposta deve ter no máximo 2000 caracteres.',
        ];
    }
}
