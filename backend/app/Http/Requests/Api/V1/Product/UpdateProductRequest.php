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
      'name'             => 'sometimes|string|max:255',
      'description'      => 'sometimes|string',
      'long_description' => 'nullable|string',
      'category_id'      => 'sometimes|uuid|exists:categories,id',
      'price'            => 'sometimes|numeric|min:0',
      'cost_price'       => 'nullable|numeric|min:0',
      'discount_price'   => 'nullable|numeric|min:0',
      'discount_percent' => 'nullable|numeric|min:0|max:100',
      'stock_quantity'   => 'sometimes|integer|min:0',
      'is_featured'      => 'boolean',
      'status'           => 'in:ACTIVE,INACTIVE,DRAFT,OUT_OF_STOCK',
      'color'            => 'nullable|string|max:50',
      'size'             => 'nullable|string|max:50',
    ];
  }

  public function messages(): array
  {
    return [
      'category_id.exists'     => 'A categoria informada não existe.',
      'price.numeric'          => 'O preço deve ser um número.',
      'stock_quantity.integer'  => 'A quantidade deve ser um número inteiro.',
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
