<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductReviewResource;
use App\Http\Requests\Api\V1\ProductReview\StoreProductReviewRequest;
use App\Http\Requests\Api\V1\ProductReview\UpdateProductReviewRequest;
use App\Http\Requests\Api\V1\ProductReview\AdminReplyReviewRequest;
use App\Services\ProductReviewService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProductReviewController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductReviewService $productReviewService
    ) {}

    /**
     * Public — paginated reviews for a product + average rating.
     */
    public function index(string $id): JsonResponse
    {
        ['reviews' => $reviews, 'stats' => $stats] = $this->productReviewService->getForProduct($id);

        return $this->success([
            'reviews' => ProductReviewResource::collection($reviews),
            'average_rating' => (float) ($stats->average_rating ?? 0),
            'total_reviews' => (int) ($stats->total_reviews ?? 0),
            'pagination' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    /**
     * Auth — create a review (one per user per product).
     */
    public function store(StoreProductReviewRequest $request, string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validated();

        $result = $this->productReviewService->create($id, $user, $data);

        if (is_string($result)) {
            return $this->error($result, 422);
        }

        return $this->success(new ProductReviewResource($result), 'Review submitted', 201);
    }

    /**
     * Auth — update own review.
     */
    public function update(UpdateProductReviewRequest $request, string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validated();

        $review = $this->productReviewService->update($id, $user, $data);

        return $this->success(new ProductReviewResource($review), 'Review updated');
    }

    /**
     * Admin — reply to a review.
     */
    public function adminReply(AdminReplyReviewRequest $request, string $id): JsonResponse
    {
        $data = $request->validated();

        $review = $this->productReviewService->addAdminReply($id, $data['admin_reply']);

        return $this->success(new ProductReviewResource($review), 'Reply added');
    }

    /**
     * Admin — list all reviews (paginated).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $filters = $request->only(['unreplied', 'rating', 'search']);
        $reviews = $this->productReviewService->adminList($filters, $request->input('limit', 15));

        return $this->success([
            'data' => ProductReviewResource::collection($reviews)->map(function ($resource) {
                $data = $resource->resolve();
                $data['product'] = [
                    'id' => $resource->resource->product?->id,
                    'name' => $resource->resource->product?->name,
                    'slug' => $resource->resource->product?->slug,
                ];

                return $data;
            }),
            'meta' => [
                'total' => $reviews->total(),
                'page' => $reviews->currentPage(),
                'limit' => $reviews->perPage(),
                'total_pages' => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * Admin — delete a review.
     */
    public function destroy(string $id): JsonResponse
    {
        $this->productReviewService->delete($id);

        return $this->success(null, 'Review deleted');
    }
}
