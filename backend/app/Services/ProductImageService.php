<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProductImageService
{
    public function getForProduct(string $productId): ?Collection
    {
        if (! Product::where('id', $productId)->exists()) {
            return null;
        }

        return ProductImage::where('product_id', $productId)->orderBy('sort_order')->get();
    }

    /**
     * @return ProductImage|string|null Returns the image on success, 'product_not_found' if product missing, null on other failure.
     */
    public function addByUrl(string $productId, array $data): ProductImage|string|null
    {
        $product = Product::find($productId);

        if (! $product) {
            return 'product_not_found';
        }

        if (! empty($data['is_primary'])) {
            $product->images()->update(['is_primary' => false]);
        }

        if ($product->images()->count() === 0) {
            $data['is_primary'] = true;
        }

        $data['product_id'] = $productId;
        $data['sort_order'] = $data['sort_order'] ?? ($product->images()->max('sort_order') + 1);

        return ProductImage::create($data);
    }

    /**
     * @return ProductImage|string|null Returns the image on success, 'product_not_found' if product missing.
     */
    public function uploadFile(string $productId, UploadedFile $file, array $data): ProductImage|string
    {
        $product = Product::find($productId);

        if (! $product) {
            return 'product_not_found';
        }

        $path = $file->store('products/'.$productId, 'public');

        if (! empty($data['is_primary'])) {
            $product->images()->update(['is_primary' => false]);
        }

        $isPrimary = $product->images()->count() === 0 ? true : ($data['is_primary'] ?? false);

        return ProductImage::create([
            'product_id' => $productId,
            'image_url' => Storage::disk('public')->url($path),
            'alt_text' => $data['alt_text'] ?? null,
            'sort_order' => $product->images()->max('sort_order') + 1,
            'is_primary' => $isPrimary,
        ]);
    }

    public function update(string $productId, string $imageId, array $data): ?ProductImage
    {
        $image = ProductImage::where('product_id', $productId)->find($imageId);

        if (! $image) {
            return null;
        }

        if (! empty($data['is_primary'])) {
            ProductImage::where('product_id', $productId)
                ->where('id', '!=', $imageId)
                ->update(['is_primary' => false]);
        }

        $image->update($data);

        return $image->fresh();
    }

    public function delete(string $productId, string $imageId): ?ProductImage
    {
        $image = ProductImage::where('product_id', $productId)->find($imageId);

        if (! $image) {
            return null;
        }

        $wasPrimary = $image->is_primary;

        $image->delete();

        if ($wasPrimary) {
            $next = ProductImage::where('product_id', $productId)
                ->orderBy('sort_order')
                ->first();
            $next?->update(['is_primary' => true]);
        }

        return $image;
    }
}
