<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Product\StoreProductRequest;
use App\Http\Requests\Api\V1\Product\UpdateProductRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\ProductService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);
        $limit = min((int) $request->get('limit', 20), 100);

        $filters = $request->only(['search', 'categoryId', 'status', 'sortBy', 'minPrice', 'maxPrice']);
        $filters['status'] = $filters['status'] ?? 'ACTIVE';

        ['products' => $products, 'total' => $total] = $this->productService->paginate($filters, $page, $limit);

        return $this->paginated(
            ProductResource::collection($products),
            $total,
            $page,
            $limit
        );
    }

    public function show(string $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (! $product) {
            return $this->error('Product not found', 404);
        }

        return $this->success(new ProductResource($product));
    }

    public function showBySlug(string $slug): JsonResponse
    {
        $product = $this->productService->findBySlug($slug);

        if (! $product) {
            return $this->error('Product not found', 404);
        }

        return $this->success(new ProductResource($product));
    }

    public function featured(Request $request): JsonResponse
    {
        $limit = (int) $request->query('limit', 8);
        $products = $this->productService->getFeatured($limit);

        return $this->success(ProductResource::collection($products));
    }

    public function categories()
    {
        $categories = $this->productService->getCategories();

        return $this->jsonResponse(CategoryResource::collection($categories));
    }

    // Admin
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create($request->validated());

        return $this->jsonResponse(new ProductResource($product), 'Product created', 201);
    }

    public function update(UpdateProductRequest $request, string $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (! $product) {
            return $this->error('Product not found', 404);
        }

        $updated = $this->productService->update($id, $request->validated());

        return $this->success(new ProductResource($updated), 'Product updated');
    }

    public function destroy(string $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (! $product) {
            return $this->error('Product not found', 404);
        }

        $this->productService->delete($id);

        return $this->success(null, 'Product deleted');
    }
}
