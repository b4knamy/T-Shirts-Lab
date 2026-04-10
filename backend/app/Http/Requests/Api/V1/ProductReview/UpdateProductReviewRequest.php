<?php

namespace App\Http\Requests\Api\V1\ProductReview;

use App\Http\Requests\Api\V1\CustomFormRequest;

class UpdateProductReviewRequest extends CustomFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rating' => ['sometimes', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'rating.integer' => 'A avaliação deve ser um número inteiro.',
            'rating.min' => 'A avaliação mínima é 1 estrela.',
            'rating.max' => 'A avaliação máxima é 5 estrelas.',
            'comment.max' => 'O comentário deve ter no máximo 2000 caracteres.',
        ];
    }
}
