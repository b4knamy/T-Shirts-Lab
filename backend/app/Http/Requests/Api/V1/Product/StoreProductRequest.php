<?php

namespace App\Http\Requests\Api\V1\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreProductRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name'           => 'required|string|max:255',
      'description'    => 'required|string',
      'longDescription' => 'nullable|string',
      'categoryId'     => 'required|uuid|exists:categories,id',
      'price'          => 'required|numeric|min:0',
      'costPrice'      => 'nullable|numeric|min:0',
      'discountPrice'  => 'nullable|numeric|min:0',
      'discountPercent' => 'nullable|numeric|min:0|max:100',
      'stockQuantity'  => 'required|integer|min:0',
      'sku'            => 'nullable|string|unique:products,sku',
      'isFeatured'     => 'boolean',
      'status'         => 'in:ACTIVE,INACTIVE,DRAFT',
      'color'          => 'nullable|string|max:50',
      'size'           => 'nullable|string|max:50',
    ];
  }

  public function messages(): array
  {
    return [
      'name.required'          => 'O nome do produto é obrigatório.',
      'description.required'   => 'A descrição é obrigatória.',
      'categoryId.required'    => 'A categoria é obrigatória.',
      'categoryId.exists'      => 'A categoria informada não existe.',
      'price.required'         => 'O preço é obrigatório.',
      'price.numeric'          => 'O preço deve ser um número.',
      'stockQuantity.required' => 'A quantidade em estoque é obrigatória.',
      'sku.unique'             => 'Este SKU já está em uso.',
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
