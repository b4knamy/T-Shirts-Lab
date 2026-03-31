<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use App\Traits\ApiResponse;
use App\Http\Resources\Api\V1\ProductImageResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductImageController extends Controller
{
  use ApiResponse;

  /* ── List images for a product ──────────────────────────────────── */
  public function index(string $productId): JsonResponse
  {
    $product = Product::find($productId);

    if (!$product) {
      return $this->error('Product not found', 404);
    }

    $images = $product->images()->orderBy('sort_order')->get();

    return $this->success(ProductImageResource::collection($images));
  }

  /* ── Add image (URL-based) ──────────────────────────────────────── */
  public function store(Request $request, string $productId): JsonResponse
  {
    $product = Product::find($productId);

    if (!$product) {
      return $this->error('Product not found', 404);
    }

    $data = $request->validate([
      'image_url'  => 'required|string|max:1000',
      'alt_text'   => 'nullable|string|max:255',
      'sort_order' => 'nullable|integer|min:0',
      'is_primary' => 'boolean',
    ]);

    // If this image is to be primary, un-primary others
    if (!empty($data['is_primary'])) {
      $product->images()->update(['is_primary' => false]);
    }

    // If no images yet, make this one primary
    if ($product->images()->count() === 0) {
      $data['is_primary'] = true;
    }

    $data['product_id'] = $productId;
    $data['sort_order'] = $data['sort_order'] ?? ($product->images()->max('sort_order') + 1);

    $image = ProductImage::create($data);

    return $this->success(new ProductImageResource($image), 'Image added', 201);
  }

  /* ── Upload image file ──────────────────────────────────────────── */
  public function upload(Request $request, string $productId): JsonResponse
  {
    $product = Product::find($productId);

    if (!$product) {
      return $this->error('Product not found', 404);
    }

    $request->validate([
      'image'      => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
      'alt_text'   => 'nullable|string|max:255',
      'is_primary' => 'boolean',
    ]);

    $path = $request->file('image')->store(
      'products/' . $productId,
      'public'
    );

    if (!empty($request->is_primary)) {
      $product->images()->update(['is_primary' => false]);
    }

    if ($product->images()->count() === 0) {
      $request->merge(['is_primary' => true]);
    }

    $image = ProductImage::create([
      'product_id' => $productId,
      'image_url'  => Storage::disk('public')->url($path),
      'alt_text'   => $request->alt_text,
      'sort_order' => $product->images()->max('sort_order') + 1,
      'is_primary' => $request->boolean('is_primary', false),
    ]);

    return $this->success(new ProductImageResource($image), 'Image uploaded', 201);
  }

  /* ── Update (alt text, sort order, primary) ─────────────────────── */
  public function update(Request $request, string $productId, string $imageId): JsonResponse
  {
    $image = ProductImage::where('product_id', $productId)->find($imageId);

    if (!$image) {
      return $this->error('Image not found', 404);
    }

    $data = $request->validate([
      'alt_text'   => 'nullable|string|max:255',
      'sort_order' => 'nullable|integer|min:0',
      'is_primary' => 'boolean',
    ]);

    if (!empty($data['is_primary'])) {
      ProductImage::where('product_id', $productId)
        ->where('id', '!=', $imageId)
        ->update(['is_primary' => false]);
    }

    $image->update($data);

    return $this->success(new ProductImageResource($image->fresh()), 'Image updated');
  }

  /* ── Delete ─────────────────────────────────────────────────────── */
  public function destroy(string $productId, string $imageId): JsonResponse
  {
    $image = ProductImage::where('product_id', $productId)->find($imageId);

    if (!$image) {
      return $this->error('Image not found', 404);
    }

    // If deleting the primary, promote next image
    $wasPrimary = $image->is_primary;

    $image->delete();

    if ($wasPrimary) {
      $next = ProductImage::where('product_id', $productId)
        ->orderBy('sort_order')
        ->first();
      $next?->update(['is_primary' => true]);
    }

    return $this->success(null, 'Image deleted');
  }
}
