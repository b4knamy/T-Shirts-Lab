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
      'name'             => 'required|string|max:255',
      'description'      => 'required|string',
      'long_description' => 'nullable|string',
      'category_id'      => 'required|uuid|exists:categories,id',
      'price'            => 'required|numeric|min:0',
      'cost_price'       => 'nullable|numeric|min:0',
      'discount_price'   => 'nullable|numeric|min:0',
      'discount_percent' => 'nullable|numeric|min:0|max:100',
      'stock_quantity'   => 'required|integer|min:0',
      'sku'              => 'nullable|string|unique:products,sku',
      'is_featured'      => 'boolean',
      'status'           => 'in:ACTIVE,INACTIVE,DRAFT',
      'color'            => 'nullable|string|max:50',
      'size'             => 'nullable|string|max:50',
    ];
  }

  public function messages(): array
  {
    return [
      'name.required'           => 'O nome do produto é obrigatório.',
      'description.required'    => 'A descrição é obrigatória.',
      'category_id.required'    => 'A categoria é obrigatória.',
      'category_id.exists'      => 'A categoria informada não existe.',
      'price.required'          => 'O preço é obrigatório.',
      'price.numeric'           => 'O preço deve ser um número.',
      'stock_quantity.required' => 'A quantidade em estoque é obrigatória.',
      'sku.unique'              => 'Este SKU já está em uso.',
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
