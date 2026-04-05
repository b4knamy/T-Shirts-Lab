<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductReviewResource;
use App\Models\Product;
use App\Models\ProductReview;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

class ProductReviewController extends Controller
{
    use ApiResponse;

    /**
     * Public — paginated reviews for a product + average rating.
     */
    public function index(string $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $reviews = $product->reviews()
            ->with('user')
            ->latest()
            ->paginate(10);

        $stats = ProductReview::where('product_id', $id)
            ->selectRaw('ROUND(AVG(rating), 1) as average_rating, COUNT(*) as total_reviews')
            ->first();

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
    public function store(Request $request, string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $product = Product::findOrFail($id);

        // Check if user already reviewed
        $existing = ProductReview::where('user_id', $user->id)
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            return $this->error('You have already reviewed this product', 422);
        }

        $review = ProductReview::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);

        $review->load('user');

        return $this->success(new ProductReviewResource($review), 'Review submitted', 201);
    }

    /**
     * Auth — update own review.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = JWTAuth::parseToken()->authenticate();

        $review = ProductReview::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $data = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
        ]);

        $review->update($data);
        $review->load('user');

        return $this->success(new ProductReviewResource($review->fresh()), 'Review updated');
    }

    /**
     * Admin — reply to a review.
     */
    public function adminReply(Request $request, string $id): JsonResponse
    {
        $data = $request->validate([
            'admin_reply' => 'required|string|max:2000',
        ]);

        $review = ProductReview::findOrFail($id);

        $review->update([
            'admin_reply' => $data['admin_reply'],
            'admin_replied_at' => now(),
        ]);

        $review->load('user');

        return $this->success(new ProductReviewResource($review->fresh()), 'Reply added');
    }

    /**
     * Admin — list all reviews (paginated).
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = ProductReview::with(['user', 'product'])
            ->latest();

        if ($request->filled('unreplied')) {
            $query->whereNull('admin_reply');
        }

        $reviews = $query->paginate($request->input('limit', 15));

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
        $review = ProductReview::findOrFail($id);
        $review->delete();

        return $this->success(null, 'Review deleted');
    }
}
