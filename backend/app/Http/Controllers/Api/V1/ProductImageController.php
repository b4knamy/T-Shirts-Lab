<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProductImage\StoreProductImageRequest;
use App\Http\Requests\Api\V1\ProductImage\UpdateProductImageRequest;
use App\Http\Requests\Api\V1\ProductImage\UploadProductImageRequest;
use App\Http\Resources\Api\V1\ProductImageResource;
use App\Services\ProductImageService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductImageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductImageService $productImageService
    ) {}

    /* ── List images for a product ──────────────────────────────────── */
    public function index(string $productId): JsonResponse
    {
        $images = $this->productImageService->getForProduct($productId);

        if ($images === null) {
            return $this->error('Product not found', 404);
        }

        return $this->success(ProductImageResource::collection($images));
    }

    /* ── Add image (URL-based) ──────────────────────────────────────── */
    public function store(StoreProductImageRequest $request, string $productId): JsonResponse
    {
        $data = $request->validated();

        $image = $this->productImageService->addByUrl($productId, $data);

        if ($image === 'product_not_found') {
            return $this->error('Product not found', 404);
        }

        return $this->success(new ProductImageResource($image), 'Image added', 201);
    }

    /* ── Upload image file ──────────────────────────────────────────── */
    public function upload(UploadProductImageRequest $request, string $productId): JsonResponse
    {
        $data = [
            'alt_text' => $request->input('alt_text'),
            'is_primary' => $request->boolean('is_primary', false),
        ];

        $image = $this->productImageService->uploadFile($productId, $request->file('image'), $data);

        if ($image === 'product_not_found') {
            return $this->error('Product not found', 404);
        }

        return $this->success(new ProductImageResource($image), 'Image uploaded', 201);
    }

    /* ── Update (alt text, sort order, primary) ─────────────────────── */
    public function update(UpdateProductImageRequest $request, string $productId, string $imageId): JsonResponse
    {
        $data = $request->validated();

        $image = $this->productImageService->update($productId, $imageId, $data);

        if (! $image) {
            return $this->error('Image not found', 404);
        }

        return $this->success(new ProductImageResource($image), 'Image updated');
    }

    /* ── Delete ─────────────────────────────────────────────────────── */
    public function destroy(string $productId, string $imageId): JsonResponse
    {
        $image = $this->productImageService->delete($productId, $imageId);

        if (! $image) {
            return $this->error('Image not found', 404);
        }

        return $this->success(null, 'Image deleted');
    }
}
