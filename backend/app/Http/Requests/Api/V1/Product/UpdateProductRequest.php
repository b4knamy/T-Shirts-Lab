<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProductRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name'           => 'sometimes|string|max:255',
      'description'    => 'sometimes|string',
      'longDescription' => 'nullable|string',
      'categoryId'     => 'sometimes|uuid|exists:categories,id',
      'price'          => 'sometimes|numeric|min:0',
      'costPrice'      => 'nullable|numeric|min:0',
      'discountPrice'  => 'nullable|numeric|min:0',
      'discountPercent' => 'nullable|numeric|min:0|max:100',
      'stockQuantity'  => 'sometimes|integer|min:0',
      'isFeatured'     => 'boolean',
      'status'         => 'in:ACTIVE,INACTIVE,DRAFT,OUT_OF_STOCK',
      'color'          => 'nullable|string|max:50',
      'size'           => 'nullable|string|max:50',
    ];
  }

  public function messages(): array
  {
    return [
      'categoryId.exists'  => 'A categoria informada não existe.',
      'price.numeric'      => 'O preço deve ser um número.',
      'stockQuantity.integer' => 'A quantidade deve ser um número inteiro.',
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
